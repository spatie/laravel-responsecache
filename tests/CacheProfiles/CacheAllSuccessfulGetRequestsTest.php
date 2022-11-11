<?php

use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Spatie\ResponseCache\Test\User;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    $this->cacheProfile = app(CacheAllSuccessfulGetRequests::class);
});

it('will determine that get requests should be cached', function () {
    assertTrue($this->cacheProfile->shouldCacheRequest(createRequest('get')));
});

it('will determine that all non get request should not be cached', function () {
    assertFalse($this->cacheProfile->shouldCacheRequest(createRequest('post')));
    assertFalse($this->cacheProfile->shouldCacheRequest(createRequest('patch')));
    assertFalse($this->cacheProfile->shouldCacheRequest(createRequest('delete')));
});

it('will determine that a successful response should be cached', function () {
    foreach (range(200, 399) as $statusCode) {
        assertTrue($this->cacheProfile->shouldCacheResponse(createResponse($statusCode)));
    }
});

it('will determine that a non text response should not be cached', function () {
    $response = createResponse(200, 'application/pdf');

    $shouldCacheResponse = $this->cacheProfile->shouldCacheResponse($response);

    assertFalse($shouldCacheResponse);
});

it('will determine that a json response should be cached', function () {
    $response = new JsonResponse(['a' => 'b']);

    $shouldCacheResponse = $this->cacheProfile->shouldCacheResponse($response);

    assertTrue($shouldCacheResponse);
});

it('will determine that an error should not be cached', function () {
    foreach (range(400, 599) as $statusCode) {
        assertFalse($this->cacheProfile->shouldCacheResponse(createResponse($statusCode)));
    }
});

it('will use the id of the logged in user to differentiate caches', function () {
    assertEquals('', $this->cacheProfile->useCacheNameSuffix(createRequest('get')));

    User::all()->map(function ($user) {
        auth()->login(User::find($user->id));
        assertEquals($user->id, $this->cacheProfile->useCacheNameSuffix(createRequest('get')));
    });
});

it('will determine to cache responses for a certain amount of time', function () {
    /** @var $expirationDate \Carbon\Carbon */
    $expirationDate = $this->cacheProfile->cacheRequestUntil(createRequest('get'));

    assertTrue($expirationDate->isFuture());
});
