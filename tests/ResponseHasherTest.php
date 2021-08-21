<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Request;
use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\Hasher\DefaultRequestHasher;
use Spatie\ResponseCache\ResponseCacheConfig;
use Spatie\ResponseCache\Test\TestClasses\TestCacheProfile;

class ResponseHasherTest extends TestCase
{
    /**
     * @throws InvalidConfig
     */
    public function setUp(): void
    {
        parent::setUp();

        $config = $this->getConfig();
        $config['cache_profile'] = TestCacheProfile::class;
        $cacheConfig = new ResponseCacheConfig($config);
        app()->instance(ResponseCacheConfig::class, $cacheConfig);
    }

    /** @test */
    public function it_can_generate_a_hash_for_a_request()
    {
        // Arrange
        $request = Request::create('https://spatie.be');
        $cacheConfig = app(ResponseCacheConfig::class);
        $requestHasher = app(DefaultRequestHasher::class);


        // Assert
        $this->assertEquals(
            'responsecache-467d6e9cb7425ed9d3e114e44eb7117f',
            $requestHasher->getHashFor($request, $cacheConfig)
        );
    }

    /** @test */
    public function it_generates_a_different_hash_per_request_host()
    {

        $cacheConfig = app(ResponseCacheConfig::class);
        $requestHasher = app(DefaultRequestHasher::class);

        $request = Request::create('https://spatie.be/example-page');
        $requestForSubdomain = Request::create('https://de.spatie.be/example-page');

        $this->assertNotEquals(
            $requestHasher->getHashFor($request, $cacheConfig),
            $requestHasher->getHashFor($requestForSubdomain, $cacheConfig)
        );
    }
}
