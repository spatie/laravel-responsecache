<?php

namespace Spatie\ResponseCache\Middlewares;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class BaseCacheMiddleware
{
    protected function getReplacers(): Collection
    {
        return collect(config('responsecache.replacers'))
            ->map(fn (string $replacerClass) => app($replacerClass));
    }

    public function addCacheAgeHeader(Response $response): Response
    {
        if (config('responsecache.add_cache_age_header') and $time = $response->headers->get(config('responsecache.cache_time_header_name'))) {
            $ageInSeconds = (int) Carbon::parse($time)->diffInSeconds(Carbon::now(), true);

            $response->headers->set(config('responsecache.cache_age_header_name'), $ageInSeconds);
        }

        return $response;
    }

    protected function getTags(array $args): array
    {
        $tags = $args;

        if (count($args) >= 1) {
            if (is_numeric($args[0]) || str_contains($args[0], ':')) {
                $tags = array_slice($args, 1);
            }
        }

        return array_filter($tags);
    }

}
