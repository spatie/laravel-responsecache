<?php

namespace Spatie\ResponseCache\Test;

use ResponseCache;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function it_will_cache_a_get_request()
    {
        $firstResponse = $this->call('GET', '/random');
        $secondResponse = $this->call('GET', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);

        $this->assertSameResponse($firstResponse, $secondResponse);
    }

    /**
     * @test
     */
    public function it_will_cache_redirects()
    {
        $firstResponse = $this->call('GET', '/redirect');
        $secondResponse = $this->call('GET', '/redirect');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);

        $this->assertSameResponse($firstResponse, $secondResponse);
    }

    /**
     * @test
     */
    public function it_will_not_cache_a_post_request()
    {
        $firstResponse = $this->call('POST', '/random');
        $secondResponse = $this->call('POST', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /**
     * @test
     */
    public function it_can_flush_the_cached_requests()
    {
        $firstResponse = $this->call('GET', '/random');
        $this->assertRegularResponse($firstResponse);

        ResponseCache::flush();

        $secondResponse = $this->call('GET', '/random');
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /**
     * @test
     */
    public function it_will_cache_responses_for_each_logged_in_user_seperately()
    {
        $this->call('GET', '/login/1');
        $firstUserFirstCall = $this->call('GET', '/');
        $firstUserSecondCall = $this->call('GET', '/');
        $this->call('GET', 'logout');

        $this->call('GET', '/login/2');
        $secondUserFirstCall = $this->call('GET', '/');
        $secondUserSecondCall = $this->call('GET', '/');
        $this->call('GET', 'logout');

        $this->assertRegularResponse($firstUserFirstCall);
        $this->assertCachedResponse($firstUserSecondCall);

        $this->assertRegularResponse($secondUserFirstCall);
        $this->assertCachedResponse($secondUserSecondCall);

        $this->assertSameResponse($firstUserFirstCall, $firstUserSecondCall);
        $this->assertSameResponse($secondUserFirstCall, $secondUserSecondCall);

        $this->assertDifferentResponse($firstUserFirstCall, $secondUserSecondCall);
        $this->assertDifferentResponse($firstUserSecondCall, $secondUserSecondCall);
    }

    /**
     * @test
     */
    public function it_will_not_cache_routes_with_the_doNotCacheResponse_middleware()
    {
        $firstResponse = $this->call('GET', '/uncacheable');
        $secondResponse = $this->call('GET', '/uncacheable');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }
}
