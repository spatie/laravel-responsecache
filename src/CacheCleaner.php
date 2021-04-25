<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Illuminate\Support\Str;

class CacheCleaner
{
    protected string $method = 'GET';
    protected array $parameters = [];
    protected array $cookies = [];
    protected array $server = [];

    public function __construct(
        protected RequestHasher $hasher,
        protected ResponseCacheRepository $cache,
    ) {
    }

    /**
     * Set the value of method
     *
     * @return  self
     */
    public function setMethod(string $method)
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Set parameters value
     * if method is GET then will be converted to query
     * otherwise it will became part of request input
     * @return  self
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Set the value of cookies
     *
     * @return  self
     */
    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * Set the value of headers
     *
     * @return  self
     */
    public function setHeaders(array $headers)
    {
        $this->server = collect($this->server)
            ->filter(function (string $val, string $key) {
                return !Str::startsWith($key, 'HTTP_');
            })->merge(collect($headers)
                ->mapWithKeys(function (string $val, string $key) {
                    return ['HTTP_' . str_replace('-', '_', Str::upper($key)) => $val];
                }))
            ->toArray();

        return $this;
    }

    /**
     * Set the value of remoteAddress
     *
     * @return  self
     */
    public function setRemoteAddress($remoteAddress)
    {
        $this->server['REMOTE_ADDR'] = $remoteAddress;
        return $this;
    }


    /**
     * @return void
     */
    public function forget(string | array $uris,  $tags = [])
    {
        if (!is_array($uris)) {
            $uris = [$uris];
        }

        $cache = $this->cache;
        if (!empty($tags)) {
            $cache = $cache->tags($tags);
        }

        collect($uris)->map(function ($uri) {
            $request = Request::create($uri, $this->method, $this->parameters, $this->cookies, [], $this->server);
            $hash = $this->hasher->getHashFor($request);
            return $hash;
        })->each(function ($hash) use ($cache) {
            if ($cache->has($hash)) {
                $cache->forget($hash);
            }
        });
    }
}
