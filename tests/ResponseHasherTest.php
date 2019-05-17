<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Request;
use Mockery;
use Spatie\ResponseCache\Hasher\DefaultHasher;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseHasherTest extends TestCase
{
    /** @var \Spatie\ResponseCache\Hasher\DefaultHasher */
    protected $requestHasher;

    /** @var \Spatie\ResponseCache\CacheProfiles\CacheProfile */
    protected $cacheProfile;

    /** @var \Illuminate\Http\Request */
    protected $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->cacheProfile = Mockery::mock(CacheProfile::class);

        $this->request = Request::create('https://spatie.be');

        $this->requestHasher = new DefaultHasher($this->cacheProfile);
    }

    /** @test */
    public function it_can_generate_a_hash_for_a_request()
    {
        $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

        $this->assertEquals('responsecache-4d4ecd81770e6753c5fab1dd274f7b45',
            $this->requestHasher->getHashFor($this->request));
    }

    /** @test */
    public function it_generates_a_different_hash_per_request_host()
    {
        $this->cacheProfile->shouldReceive('useCacheNameSuffix')->andReturn('cacheProfileSuffix');

        $request = Request::create('https://spatie.be/example-page');
        $requestForSubdomain = Request::create('https://de.spatie.be/example-page');

        $this->assertNotEquals($this->requestHasher->getHashFor($request),
            $this->requestHasher->getHashFor($requestForSubdomain));
    }
}
