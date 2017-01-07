<?php

namespace Spatie\ResponseCache\Test\CacheProfiles;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequestsBasedOnSessionId;
use Spatie\ResponseCache\Test\TestCase;

class CacheAllSuccessfulGetRequestsBasedOnSessionIdTest extends TestCase
{
    /**
     * @var \Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequestsBasedOnSessionId
     */
    protected $cacheProfile;

    public function setUp()
    {
        parent::setUp();

        $this->cacheProfile = app(CacheAllSuccessfulGetRequestsBasedOnSessionId::class);
    }


    /**
     * @test
     */
    public function it_will_use_the_session_id_to_differentiate_caches()
    {
        $request = new Request();
        $request->setMethod('get');
        $this->assertNotEmpty($this->cacheProfile->cacheNameSuffix($request));
    }

}
