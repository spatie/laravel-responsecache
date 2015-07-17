<?php

namespace Spatie\ResponseCache\Test\CacheProfiles;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ResponseCache;
use Spatie\ResponseCache\CacheProfiles\CacheAllGetRequests;
use Spatie\ResponseCache\Test\TestCase;
use Spatie\ResponseCache\Test\User;

class CacheAllGetRequestsTest extends TestCase
{
    /**
     * @var \Spatie\ResponseCache\CacheProfiles\CacheAllGetRequests
     */
    protected $cacheProfile;

    public function setUp()
    {
        parent::setUp();

        $this->cacheProfile = app(CacheAllGetRequests::class);
    }

    /**
     * @test
     */
    public function it_will_determine_that_get_requests_should_be_cached()
    {
        $this->assertTrue($this->cacheProfile->shouldCache($this->createRequest('get')));
    }

    /**
     * @test
     */
    public function it_will_determine_that_all_non_get_request_should_not_be_cached()
    {
        $this->assertFalse($this->cacheProfile->shouldCache($this->createRequest('post')));
        $this->assertFalse($this->cacheProfile->shouldCache($this->createRequest('patch')));
        $this->assertFalse($this->cacheProfile->shouldCache($this->createRequest('delete')));
    }

    /**
     * @test
     */
    public function it_will_use_the_id_of_the_logged_in_user_to_differentiate_caches()
    {
        $this->assertEquals('', $this->cacheProfile->cacheNameSuffix($this->createRequest('get')));

        User::all()->map(function ($user) {
            auth()->login(User::find($user->id));
            $this->assertEquals($user->id, $this->cacheProfile->cacheNameSuffix($this->createRequest('get')));
        });
    }

    /**
     * @test
     */
    public function it_will_determine_to_cache_reponses_for_a_certain_amount_of_time()
    {
        /** @var $expirationDate Carbon  */
        $expirationDate = $this->cacheProfile->cacheRequestUntil($this->createRequest('get'));

        $expirationDate->isFuture();
    }

    /**
     * Create a new request with the given method.
     *
     * @param $method
     *
     * @return \Illuminate\Http\Request
     */
    protected function createRequest($method)
    {
        $request = new Request();

        $request->setMethod($method);

        return $request;
    }
}
