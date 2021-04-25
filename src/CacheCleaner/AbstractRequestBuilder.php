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

    /**
     * Set the value of method
     *
     * @return  static
     */
    public function setMethod(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Set parameters value
     * if method is GET then will be converted to query
     * otherwise it will became part of request input
     * @return  static
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Set the value of cookies
     *
     * @return  static
     */
    public function setCookies(array $cookies): static
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * Set the value of headers
     *
     * @return  static
     */
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

    /**
     * Set the value of remoteAddress
     *
     * @return  static
     */
    public function setRemoteAddress($remoteAddress): static
    {
        $this->server['REMOTE_ADDR'] = $remoteAddress;
        return $this;
    }


    /**
     * @return Request
     */
    protected function _build(string $uri): Request
    {
        return Request::create(
            $uri,
            $this->method,
            $this->parameters,
            $this->cookies,
            [],
            $this->server
        );
    }
}
