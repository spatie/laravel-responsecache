<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Cache\Repository;
use Mockery;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Serializers\Serializer;

class ResponseCacheRepositoryTest extends TestCase
{
    /** @test */
    public function it_handles_missed_cache_gracefully()
    {
        // Instantiate a default serializer
        $responseSerializer = app(Serializer::class);

        $cacheRepository = Mockery::mock(Repository::class);
        $cacheRepository->shouldReceive('get')->with('missed-cache')->once()->andReturn(null);

        $this->expectException(CouldNotUnserialize::class);

        $repository = new ResponseCacheRepository($responseSerializer, $cacheRepository);
        $repository->get('missed-cache');
    }
}
