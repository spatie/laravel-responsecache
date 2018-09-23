<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Request;
use Spatie\ResponseCache\RequestHasher;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

class ResponseHasherTest extends TestCase
{
    /**
     * @var \Spatie\ResponseCache\RequestHasher
     */
    protected $requestHasher;

    protected $cacheProfile;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    public function setUp()
    {
        parent::setUp();

        $this->cacheProfile = \Mockery::mock(CacheProfile::class);

        $this->request = Request::create('https://spatie.be');

        $this->requestHasher = new RequestHasher($this->cacheProfile);
    }

    /** @test */
    public function it_can_generate_a_hash_for_a_request()
    {
        $this->cacheProfile->shouldReceive('cacheNameSuffix')->andReturn('cacheProfileSuffix');

        $this->assertEquals('responsecache-1906a94776759c109dba2177825ade33',
            $this->requestHasher->getHashFor($this->request));
    }
}
