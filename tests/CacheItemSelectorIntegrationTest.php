<?php

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\ResponseCache\Test\User;

beforeAll(function () {
    class CacheSuccessfulGetAndPostRequests extends CacheAllSuccessfulGetRequests
    {
        public function shouldCacheRequest(Request $request): bool
        {
            if ($request->ajax()) {
                return false;
            }
            if ($this->isRunningInConsole()) {
                return false;
            }

            return $request->isMethod('get') || $request->isMethod('post');
        }
    }
});

beforeEach(function () {
    config()->set(
        'responsecache.cache_profile',
        CacheSuccessfulGetAndPostRequests::class
    );
});

it('will cache a post request', function () {
    $firstResponse = $this->call('POST', '/random');
    $secondResponse = $this->call('POST', '/random');

    assertRegularResponse($firstResponse);
    assertCachedResponse($secondResponse);

    assertSameResponse($firstResponse, $secondResponse);
});

it('can forget a specific cached request using cache cleaner', function () {
    $firstResponse = $this->get('/random?foo=bar');
    assertRegularResponse($firstResponse);

    ResponseCache::selectCachedItems()->withParameters(['foo' => 'bar'])
        ->forUrls('/random')->forget();

    $secondResponse = $this->get('/random?foo=bar');
    assertRegularResponse($secondResponse);

    assertDifferentResponse($firstResponse, $secondResponse);
});

it('can forget a specific cached request using cache cleaner post', function () {
    $firstResponse = $this->post('/random');
    assertRegularResponse($firstResponse);

    ResponseCache::selectCachedItems()->withPostMethod()->forUrls('/random')->forget();

    $secondResponse = $this->post('/random');
    assertRegularResponse($secondResponse);

    assertDifferentResponse($firstResponse, $secondResponse);
});

it('can forget several specific cached requests at once using cache cleaner', function () {
    $firstResponseFirstCall = $this->get('/random/1?foo=bar');
    assertRegularResponse($firstResponseFirstCall);

    $secondResponseFirstCall = $this->get('/random/2?foo=bar');
    assertRegularResponse($secondResponseFirstCall);

    ResponseCache::selectCachedItems()->withParameters(['foo' => 'bar'])
        ->forUrls(['/random/1', '/random/2'])->forget();

    $firstResponseSecondCall = $this->get('/random/1?foo=bar');
    assertRegularResponse($firstResponseSecondCall);

    $secondResponseSecondCall = $this->get('/random/2?foo=bar');
    assertRegularResponse($secondResponseSecondCall);

    assertDifferentResponse($firstResponseFirstCall, $firstResponseSecondCall);
    assertDifferentResponse($secondResponseFirstCall, $secondResponseSecondCall);
});

it('can forget several specific cached requests at once using cache cleaner post', function () {
    $firstResponseFirstCall = $this->post('/random/1');
    assertRegularResponse($firstResponseFirstCall);

    $secondResponseFirstCall = $this->post('/random/2');
    assertRegularResponse($secondResponseFirstCall);

    ResponseCache::selectCachedItems()->withPostMethod()->forUrls(['/random/1', '/random/2'])->forget();

    $firstResponseSecondCall = $this->post('/random/1');
    assertRegularResponse($firstResponseSecondCall);

    $secondResponseSecondCall = $this->post('/random/2');
    assertRegularResponse($secondResponseSecondCall);

    assertDifferentResponse($firstResponseFirstCall, $firstResponseSecondCall);
    assertDifferentResponse($secondResponseFirstCall, $secondResponseSecondCall);
});

it('can forget a specific cached request using cache cleaner suffix', function () {
    $userId = 1;

    $this->actingAs(User::findOrFail($userId));
    $firstResponse = $this->get('/random?foo=bar');
    assertRegularResponse($firstResponse);
    auth()->logout();

    ResponseCache::selectCachedItems()
        ->withParameters(['foo' => 'bar'])
        // BaseCacheProfile an user is logged in
        // use user id as suffix
        ->usingSuffix((string)$userId)
        ->forUrls('/random')->forget();

    $this->actingAs(User::findOrFail(1));
    $secondResponse = $this->get('/random?foo=bar');
    auth()->logout();

    assertRegularResponse($secondResponse);
    assertDifferentResponse($firstResponse, $secondResponse);
});
