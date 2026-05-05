<?php

use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Exceptions;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Serializers\Serializer;

it('returns null when the cache has no value for the given key', function () {
    $responseSerializer = app(Serializer::class);

    $cacheRepository = Mockery::mock(Repository::class);
    $cacheRepository->shouldReceive('get')->with('missed-cache')->once()->andReturn(null);

    $repository = new ResponseCacheRepository($responseSerializer, $cacheRepository);

    expect($repository->get('missed-cache'))->toBeNull();
});

it('treats a cache key that vanished mid-request as a miss without reporting', function () {
    // Simulates the race where a cached key exists at one moment but the
    // value is gone microseconds later (TTL expiry, eviction, or a
    // concurrent forget). The middleware must treat this as a regular cache
    // miss and serve a fresh response without reporting an exception.
    Exceptions::fake();

    $cacheStore = app('cache')->store('array')->getStore();

    $cacheRepository = Mockery::mock(Repository::class, [$cacheStore])->makePartial();
    $cacheRepository->shouldReceive('has')->andReturn(true);
    $cacheRepository->shouldReceive('get')->andReturn(null);
    $this->instance(Repository::class, $cacheRepository);

    $response = $this->get('/random');

    assertRegularResponse($response);
    Exceptions::assertNothingReported();
});
