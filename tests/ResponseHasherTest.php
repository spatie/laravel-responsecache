<?php

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\DefaultHasher;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEquals;

beforeEach(function () {
    $this->cacheProfile = Mockery::mock(CacheProfile::class);

    $this->request = Request::create('https://spatie.be');

    $this->requestHasher = new DefaultHasher($this->cacheProfile);
});

it('can generate a hash for a request', function () {
    $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

    assertEquals(
        'efb1af613392e6d53391e8c792ef2d24',
        $this->requestHasher->getHashFor($this->request)
    );
});

it('generates a different hash per request host', function () {
    $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

    $request = Request::create('https://spatie.be/example-page');
    $requestForSubdomain = Request::create('https://de.spatie.be/example-page');

    assertNotEquals(
        $this->requestHasher->getHashFor($request),
        $this->requestHasher->getHashFor($requestForSubdomain)
    );
});

it('generates the same hash when ignored query parameters are present', function () {
    $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

    config()->set('responsecache.ignored_query_parameters', ['utm_source', 'gclid']);

    $request = Request::create('https://spatie.be/page');
    $requestWithUtm = Request::create('https://spatie.be/page?utm_source=google');
    $requestWithGclid = Request::create('https://spatie.be/page?gclid=abc123');
    $requestWithBoth = Request::create('https://spatie.be/page?utm_source=google&gclid=abc123');

    $baseHash = $this->requestHasher->getHashFor($request);

    assertEquals($baseHash, $this->requestHasher->getHashFor($requestWithUtm));
    assertEquals($baseHash, $this->requestHasher->getHashFor($requestWithGclid));
    assertEquals($baseHash, $this->requestHasher->getHashFor($requestWithBoth));
});

it('preserves non-ignored query parameters in the hash', function () {
    $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

    config()->set('responsecache.ignored_query_parameters', ['utm_source']);

    $request = Request::create('https://spatie.be/page?category=news');
    $requestWithUtm = Request::create('https://spatie.be/page?category=news&utm_source=google');

    assertEquals(
        $this->requestHasher->getHashFor($request),
        $this->requestHasher->getHashFor($requestWithUtm),
    );

    $requestDifferentCategory = Request::create('https://spatie.be/page?category=sports');

    assertNotEquals(
        $this->requestHasher->getHashFor($request),
        $this->requestHasher->getHashFor($requestDifferentCategory),
    );
});

it('does not ignore query parameters when the config is empty', function () {
    $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

    config()->set('responsecache.ignored_query_parameters', []);

    $request = Request::create('https://spatie.be/page');
    $requestWithParam = Request::create('https://spatie.be/page?utm_source=google');

    assertNotEquals(
        $this->requestHasher->getHashFor($request),
        $this->requestHasher->getHashFor($requestWithParam),
    );
});
