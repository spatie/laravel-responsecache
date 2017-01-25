<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Request;
use ResponseCache;

class IntegrationTest extends TestCase
{
    use \Laravel\BrowserKitTesting\Concerns\MakesHttpRequests;

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
        if (starts_with($this->app->version(), "5.1")) {
            $this->markTestSkipped("This test only works in modern versions of Laravel");
        }

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
        $this->app['config']->set('laravel-responsecache.enabled', false);

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

        $this->app['config']->set('laravel-responsecache.enabled', false);

        $secondResponse = $this->call('GET', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_can_use_the_session_id_to_differentiate_caches()
    {
        $request = new Request();
        $request->setMethod('get');

        $this->assertNotFalse(app(UseSessionCacheProfile::class)->cacheNameSuffix($request));
    }
}
