<?php

use Illuminate\Cache\Repository;
use Illuminate\Testing\TestResponse;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Serializers\Serializer;

it('handles missed cache gracefully', function () {
    // Instantiate a default serializer
    $responseSerializer = app(Serializer::class);

    $cacheRepository = Mockery::mock(Repository::class);
    $cacheRepository->shouldReceive('get')->with('missed-cache')->once()->andReturn(null);

    $repository = new ResponseCacheRepository($responseSerializer, $cacheRepository);
    $repository->get('missed-cache');
})->throws(CouldNotUnserialize::class);

it('will handle race conditions between has and get', function () {

    /** @var Serializer $responseSerializer */
    $responseSerializer = app(Serializer::class);
    /** @var Illuminate\Cache\ArrayStore $cacheStore */
    $cacheStore = app('cache')
        ->store('array')
        ->getStore();

    // This order of operations simulates a cache lookup happening during a
    // cache expiration or purge event. The `has()` call should succeed, but
    // after that the cache has 'expired' and is unavailable.
    $cachedValues = [
        $responseSerializer->serialize(createResponse(200)),
        null,
    ];

    // We cannot use the partialMock helper because the cache store must be
    // available and partialMock does not allow constructor arguments.
    $cacheRepository = Mockery::mock(Repository::class, [$cacheStore]);
    $cacheRepository
            ->shouldReceive('get')
            ->twice()
            ->andReturns($cachedValues);
    $cacheRepository->makePartial();
    $this->instance(Repository::class, $cacheRepository);

    /** @var TestResponse $response */
    $response = $this->get('/random');
    assertRegularResponse($response);
    expect($response->exception)->toBeNull();
});
