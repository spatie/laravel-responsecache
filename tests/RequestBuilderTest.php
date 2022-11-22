<?php

use Illuminate\Http\Request;

use function PHPUnit\Framework\assertEquals;

use Spatie\ResponseCache\CacheItemSelector\AbstractRequestBuilder;

beforeAll(function () {
    class RequestBuilder extends AbstractRequestBuilder
    {
        public function testBuild(string $uri): Request
        {
            return $this->build($uri);
        }
    }
});

it('request builder works', function () {
    $uri = '/foo';

    $cookies = [
        'cookie1' => 'cookie1_value',
        'cookie2' => 'cookie2_value',
    ];

    $headers = [
        'Header1' => 'Header1_value',
        'Header2' => 'Header2_value',
    ];

    $parameters = [
        'Param1' => 'Param1_value',
        'Param2' => 'Param2_value',
    ];

    $cacheNameSuffix = 'suffix';

    $request = (new RequestBuilder())
        ->withParameters($parameters)
        ->withHeaders($headers)
        ->withCookies($cookies)
        ->withRemoteAddress('127.0.1.1')
        ->usingSuffix($cacheNameSuffix)
        ->testBuild($uri);

    foreach ($parameters as $key => $value) {
        assertEquals($request->query($key), $value);
    }

    foreach ($headers as $key => $value) {
        assertEquals($request->header($key), $value);
    }

    foreach ($cookies as $key => $value) {
        assertEquals($request->cookie($key), $value);
    }
    assertEquals($request->getRequestUri(), $uri . '?' . http_build_query($parameters));
    assertEquals($request->getMethod(), 'GET');
    assertEquals($request->ip(), '127.0.1.1');
    assertEquals($request->attributes->get('responsecache.cacheNameSuffix'), $cacheNameSuffix);


    $request = (new RequestBuilder())
        ->withPostMethod()
        ->withParameters($parameters)
        ->withHeaders($headers)
        ->withCookies($cookies)
        ->withRemoteAddress('127.0.1.1')
        ->usingSuffix($cacheNameSuffix)
        ->testBuild($uri);

    foreach ($parameters as $key => $value) {
        assertEquals($request->input($key), $value);
    }
    foreach ($headers as $key => $value) {
        assertEquals($request->header($key), $value);
    }
    foreach ($cookies as $key => $value) {
        assertEquals($request->cookie($key), $value);
    }
    assertEquals($request->getRequestUri(), $uri);
    assertEquals($request->getMethod(), 'POST');
    assertEquals($request->ip(), '127.0.1.1');
    assertEquals($request->attributes->get('responsecache.cacheNameSuffix'), $cacheNameSuffix);
});
