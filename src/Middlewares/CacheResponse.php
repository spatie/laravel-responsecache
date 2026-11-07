<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Cache\Lock as CacheLock;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;
use Spatie\ResponseCache\Configuration\CacheConfiguration;
use Spatie\ResponseCache\Events\CacheMissedEvent;
use Spatie\ResponseCache\Events\ResponseCacheHitEvent;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CacheResponse extends BaseCacheMiddleware
{
    protected string $pendingCacheAttribute = '_response_cache.pending';

    protected string $lockAttribute = '_response_cache.lock';

    public function __construct(
        protected ResponseCache $responseCache,
    ) {}

    public static function for(
        int|CarbonInterval|null $lifetime = null,
        string|array $tags = [],
    ): string {
        $lifetimeInSeconds = $lifetime instanceof CarbonInterval
            ? (int) $lifetime->totalSeconds
            : $lifetime;

        $config = new CacheConfiguration(
            lifetime: $lifetimeInSeconds,
            tags: is_array($tags) ? $tags : [$tags],
        );

        return static::class.':'.base64_encode(serialize($config));
    }

    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $attribute = $this->getAttributeFromRequest($request);

        if ($attribute instanceof NoCache) {
            return $next($request);
        }

        if ($attribute instanceof FlexibleCache) {
            return app(FlexibleCacheResponse::class)->handle($request, $next);
        }

        if (! $this->responseCache->enabled($request) || $this->responseCache->shouldBypass($request)) {
            return $next($request);
        }

        $config = $attribute instanceof Cache
            ? $attribute
            : $this->getConfigurationFromArgs($args) ?? new CacheConfiguration;

        $lifetimeInSeconds = $config->lifetime;
        $tags = $config->tags;

        if ($cachedResponse = $this->getCachedResponse($request, $tags)) {
            return $cachedResponse;
        }

        if ($this->acquireLock($request) && $cachedResponse = $this->getCachedResponse($request, $tags)) {
            $this->releaseLock($request);

            return $cachedResponse;
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->releaseLock($request);

            throw $exception;
        }

        $cacheKey = $this->responseCache->cacheKeyFor($request);

        if ($this->responseCache->shouldCache($request, $response)) {
            $request->attributes->set($this->pendingCacheAttribute, [
                'lifetime' => $lifetimeInSeconds,
                'tags' => $tags,
                'cacheKey' => $cacheKey,
                'statusCode' => $response->getStatusCode(),
                'mediaType' => $this->getMediaType($response),
            ]);
        } else {
            $this->releaseLock($request);
        }

        $response = $this->addDebugHeaders($response, false, $cacheKey);

        event(new CacheMissedEvent($request));

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $pending = $request->attributes->get($this->pendingCacheAttribute);

            if (! $pending) {
                return;
            }

            if ($this->differsFromApprovedResponse($response, $pending)) {
                return;
            }

            if (isset($pending['cacheKey'])) {
                $request->attributes->set('responsecache.cacheKey', $pending['cacheKey']);
            }

            $this->cacheResponse($request, $response, $pending['lifetime'], $pending['tags']);
        } finally {
            $this->releaseLock($request);
        }
    }

    protected function acquireLock(Request $request): ?Lock
    {
        if (! config('responsecache.lock.enabled')
            || $request->attributes->has($this->lockAttribute)
            || ! $this->responseCache->shouldCacheRequest($request)
        ) {
            return null;
        }

        $lock = $this->responseCache->lockFor($request, (int) config('responsecache.lock.lifetime_in_seconds', 10));

        if (! $lock) {
            return null;
        }

        if ($lock instanceof CacheLock) {
            $lock->betweenBlockedAttemptsSleepFor(50);
        }

        try {
            $lock->block((int) config('responsecache.lock.wait_in_seconds', 5));
        } catch (LockTimeoutException) {
            return null;
        }

        $request->attributes->set($this->lockAttribute, $lock);

        return $lock;
    }

    protected function releaseLock(Request $request): void
    {
        $request->attributes->get($this->lockAttribute)?->release();
        $request->attributes->remove($this->lockAttribute);
    }

    protected function differsFromApprovedResponse(Response $response, array $pending): bool
    {
        if (! isset($pending['statusCode'], $pending['mediaType'])) {
            return false;
        }

        if ($response->getStatusCode() !== $pending['statusCode']) {
            return true;
        }

        return $this->getMediaType($response) !== $pending['mediaType'];
    }

    protected function getMediaType(Response $response): string
    {
        $contentType = $response->headers->get('Content-Type', '');

        return strtolower(trim(Str::before($contentType, ';')));
    }

    protected function getCachedResponse(Request $request, array $tags): ?Response
    {
        $cacheKey = $this->responseCache->cacheKeyFor($request);

        try {
            $response = $this->responseCache->getCachedResponseFor($request, $tags, $cacheKey);
        } catch (Throwable $exception) {
            report("Could not serve cached response: {$exception->getMessage()}");

            return null;
        }

        if ($response === null) {
            return null;
        }

        $ageInSeconds = $this->getAgeInSeconds($response);

        event(new ResponseCacheHitEvent($request, $ageInSeconds, $tags));

        $response = $this->addDebugHeaders($response, true, $cacheKey, $ageInSeconds);

        $this->getReplacers()->each(fn (Replacer $replacer) => $replacer->replaceInCachedResponse($response));

        return $response;
    }

    protected function cacheResponse(
        Request $request,
        Response $response,
        ?int $lifetimeInSeconds,
        array $tags,
    ): void {
        $cachedResponse = clone $response;

        $cachedResponse->headers->remove(config('responsecache.debug.cache_status_header_name'));
        $cachedResponse->headers->remove('Set-Cookie');

        $this->addCacheTimeHeader($cachedResponse);

        $this->prepareResponseToCache($cachedResponse);

        $this->responseCache->cacheResponse($request, $cachedResponse, $lifetimeInSeconds, $tags);
    }

    protected function getConfigurationFromArgs(array $args): ?CacheConfiguration
    {
        if (! isset($args[0]) || ! is_string($args[0])) {
            return null;
        }

        try {
            $decoded = base64_decode($args[0], true);

            if ($decoded === false) {
                return null;
            }

            $config = unserialize($decoded);

            return $config instanceof CacheConfiguration ? $config : null;
        } catch (Throwable) {
            return null;
        }
    }
}
