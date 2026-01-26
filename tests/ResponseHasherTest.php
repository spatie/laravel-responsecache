<?php

use Illuminate\Http\Request;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEquals;

use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\DefaultHasher;

beforeEach(function () {
    $this->cacheProfile = Mockery::mock(CacheProfile::class);

    $this->request = Request::create('https://spatie.be');

    $this->requestHasher = new DefaultHasher($this->cacheProfile);
});

it('can generate a hash for a request', function () {
    $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

    assertEquals(
        'responsecache-c56adbbafa610d6b8c2fd28c3247382a',
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
