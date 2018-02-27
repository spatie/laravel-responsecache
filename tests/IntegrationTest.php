<?php

namespace Spatie\ResponseCache\Test;

use Carbon\Carbon;
use ResponseCache;
use Illuminate\Support\Facades\Event;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function it_will_cache_a_get_request()
    {
        $firstResponse = $this->call('get', '/random');
        $secondResponse = $this->call('get', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);

        $this->assertSameResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_will_fire_an_event_when_responding_without_cache()
    {
        Event::fake();

        $this->call('get', '/random');

        Event::assertDispatched(CacheMissed::class);
    }

    /** @test */
    public function it_will_fire_an_event_when_responding_from_cache()
    {
        Event::fake();

        $this->call('get', '/random');
        $this->call('get', '/random');

        Event::assertDispatched(ResponseCacheHit::class);
    }

    /** @test */
    public function it_will_cache_redirects()
    {
        $firstResponse = $this->call('GET', '/redirect');
        $secondResponse = $this->call('GET', '/redirect');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);

        $this->assertSameResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_will_not_cache_errors()
    {
        $firstResponse = $this->call('GET', '/notfound');
        $secondResponse = $this->call('GET', '/notfound');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);
    }

    /** @test */
    public function it_will_not_cache_a_post_request()
    {
        $firstResponse = $this->call('POST', '/random');
        $secondResponse = $this->call('POST', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_can_flush_the_cached_requests()
    {
        $firstResponse = $this->call('GET', '/random');
        $this->assertRegularResponse($firstResponse);

        ResponseCache::flush();

        $secondResponse = $this->call('GET', '/random');
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_can_forget_a_specific_cached_request()
    {
        $firstResponse = $this->call('GET', '/random');
        $this->assertRegularResponse($firstResponse);

        ResponseCache::forget('/random');

        $secondResponse = $this->call('GET', '/random');
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_can_forget_several_specific_cached_requests_at_once()
    {
        $firstResponseFirstCall = $this->call('GET', '/random/1');
        $this->assertRegularResponse($firstResponseFirstCall);

        $secondResponseFirstCall = $this->call('GET', '/random/2');
        $this->assertRegularResponse($secondResponseFirstCall);

        ResponseCache::forget(['/random/1', '/random/2']);

        $firstResponseSecondCall = $this->call('GET', '/random/1');
        $this->assertRegularResponse($firstResponseSecondCall);

        $secondResponseSecondCall = $this->call('GET', '/random/2');
        $this->assertRegularResponse($secondResponseSecondCall);

        $this->assertDifferentResponse($firstResponseFirstCall, $firstResponseSecondCall);
        $this->assertDifferentResponse($secondResponseFirstCall, $secondResponseSecondCall);
    }

    /** @test */
    public function it_will_cache_responses_for_each_logged_in_user_separately()
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

    /** @test */
    public function it_will_not_cache_routes_with_the_doNotCacheResponse_middleware()
    {
        $firstResponse = $this->call('GET', '/uncacheable');
        $secondResponse = $this->call('GET', '/uncacheable');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_will_not_cache_request_when_the_package_is_not_enable()
    {
        $this->app['config']->set('responsecache.enabled', false);

        $firstResponse = $this->call('GET', '/random');
        $secondResponse = $this->call('GET', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_will_not_serve_cached_requests_when_it_is_disabled_in_the_config_file()
    {
        $firstResponse = $this->call('GET', '/random');

        $this->app['config']->set('responsecache.enabled', false);

        $secondResponse = $this->call('GET', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_will_cache_file_responses()
    {
        $firstResponse = $this->call('get', '/image');
        $secondResponse = $this->call('get', '/image');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);

        $this->assertSameResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_wont_cache_if_lifetime_is_0()
    {
        $this->app['config']->set('responsecache.cache_lifetime_in_minutes', 0);

        $firstResponse = $this->call('get', '/');
        $secondResponse = $this->call('get', '/');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);
    }

    /** @test */
    public function it_will_cache_response_for_given_lifetime_which_is_defined_as_middleware_parameter()
    {
        // Set default lifetime as 0 to check if it will cache for given lifetime
        $this->app['config']->set('responsecache.cache_lifetime_in_minutes', 0);

        $firstResponse = $this->call('get', '/cache-for-given-lifetime');
        $secondResponse = $this->call('get', '/cache-for-given-lifetime');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);
    }

    /** @test */
    public function it_will_reproduce_cache_if_given_lifetime_is_expired()
    {
        // Set default lifetime as 0 to disable middleware that is already pushed to Kernel
        $this->app['config']->set('responsecache.cache_lifetime_in_minutes', 0);

        Carbon::setTestNow(Carbon::now()->subMinutes(6));
        $firstResponse = $this->call('get', '/cache-for-given-lifetime');
        $this->assertRegularResponse($firstResponse);

        $secondResponse = $this->call('get', '/cache-for-given-lifetime');
        $this->assertCachedResponse($secondResponse);

        Carbon::setTestNow();
        $thirdResponse = $this->call('get', '/cache-for-given-lifetime');
        $this->assertRegularResponse($thirdResponse);
    }
}
