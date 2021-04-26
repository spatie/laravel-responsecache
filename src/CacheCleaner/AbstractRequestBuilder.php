<?php

namespace Spatie\ResponseCache\CacheCleaner;

use Illuminate\Http\Request;
use Illuminate\Support\Str;


abstract class AbstractRequestBuilder
{
    protected string $method = 'GET';
    protected array $parameters = [];
    protected array $cookies = [];
    protected array $server = [];
    protected ?string $cacheNameSuffix = null;


    public function setMethod(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * if method is GET then will be converted to query
     * otherwise it will became part of request input
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }


    public function setCookies(array $cookies): static
    {
        $this->cookies = $cookies;

        return $this;
    }


    public function setHeaders(array $headers): static
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


    public function setRemoteAddress($remoteAddress): static
    {
        $this->server['REMOTE_ADDR'] = $remoteAddress;
        return $this;
    }


    public function setCacheNameSuffix($cacheNameSuffix): static
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
