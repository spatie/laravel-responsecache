<?php

namespace Spatie\ResponseCache\CacheItemSelector;

use Illuminate\Http\Request;
use Illuminate\Support\Str;


abstract class AbstractRequestBuilder
{
    protected string $method = 'GET';
    protected array $parameters = [];
    protected array $cookies = [];
    protected array $server = [];
    protected ?string $cacheNameSuffix = null;


    public function withPutMethod(): static
    {
        $this->method = 'PUT';
        return $this;
    }
    public function withPatchMethod(): static
    {
        $this->method = 'PATCH';
        return $this;
    }
    public function withPostMethod(): static
    {
        $this->method = 'POST';
        return $this;
    }

    /**
     * if method is GET then will be converted to query
     * otherwise it will became part of request input
     */
    public function withParameters(array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }


    public function withCookies(array $cookies): static
    {
        $this->cookies = $cookies;
        return $this;
    }


    public function withHeaders(array $headers): static
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


    public function withRemoteAddress($remoteAddress): static
    {
        $this->server['REMOTE_ADDR'] = $remoteAddress;
        return $this;
    }


    public function usingSuffix($cacheNameSuffix): static
    {
        $this->cacheNameSuffix = $cacheNameSuffix;
        return $this;
    }


    protected function _build(string $uri): Request
    {
        $request =  Request::create(
            $uri,
            $this->method,
            $this->parameters,
            $this->cookies,
            [],
            $this->server
        );

        if (isset($this->cacheNameSuffix)) {
            $request->attributes->add(['responsecache.cacheNameSuffix' => $this->cacheNameSuffix]);
        }

        return $request;
    }
}
