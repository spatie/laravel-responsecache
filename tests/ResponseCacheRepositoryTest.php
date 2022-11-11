<?php

use Illuminate\Cache\Repository;
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
