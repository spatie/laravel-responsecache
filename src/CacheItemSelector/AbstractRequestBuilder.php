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

    public function withPutMethod(): self
    {
        $this->method = 'PUT';

        return $this;
    }

    public function withPatchMethod(): self
    {
        $this->method = 'PATCH';

        return $this;
    }

    public function withPostMethod(): self
    {
        $this->method = 'POST';

        return $this;
    }

    /**
     * if method is GET then will be converted to query
     * otherwise it will became part of request input
     */
    public function withParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function withCookies(array $cookies): self
    {
        $this->cookies = $cookies;

        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->server = collect($this->server)
            ->filter(fn (string $val, string $key) => ! str_starts_with($key, 'HTTP_'))
            ->merge(collect($headers)
                ->mapWithKeys(function (string $val, string $key) {
                    return ['HTTP_' . str_replace('-', '_', Str::upper($key)) => $val];
                }))
            ->toArray();

        return $this;
    }

    public function withRemoteAddress($remoteAddress): self
    {
        $this->server['REMOTE_ADDR'] = $remoteAddress;

        return $this;
    }

    public function usingSuffix($cacheNameSuffix): self
    {
        $this->cacheNameSuffix = $cacheNameSuffix;

        return $this;
    }

    protected function build(string $uri): Request
    {
        $request = Request::create(
            url($uri),
            $this->method,
            $this->parameters,
            $this->cookies,
            [],
            $this->server
        );

        if (isset($this->cacheNameSuffix)) {
            $request->attributes->add([
                'responsecache.cacheNameSuffix' => $this->cacheNameSuffix,
            ]);
        }

        return $request;
    }
}
