<?php

namespace Spatie\ResponseCache\Test;

class TaggingTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Set the driver to array (tags don't work with the file driver)
        $app['config']->set('responsecache.cache_store', 'array');
        $app['config']->set('responsecache.cache_tag', 'tagging-test');
    }

    /** @test */
    public function it_can_cache_requests_using_route_cache_tags()
    {
        $firstResponse = $this->call('GET', '/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $secondResponse = $this->call('GET', '/tagged/1');
        $this->assertCachedResponse($secondResponse);
        $this->assertSameResponse($firstResponse, $secondResponse);

        $thirdResponse = $this->call('GET', '/tagged/2');
        $this->assertRegularResponse($thirdResponse);

        $fourthResponse = $this->call('GET', '/tagged/2');
        $this->assertCachedResponse($fourthResponse);
        $this->assertSameResponse($thirdResponse, $fourthResponse);
    }

    /** @test */
    public function it_can_forget_requests_using_route_cache_tags()
    {
        $firstResponse = $this->call('GET', '/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $this->app['responsecache']->clear(['foo']);

        $secondResponse = $this->call('GET', '/tagged/1');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);

        $this->app['responsecache']->clear();

        $thirdResponse = $this->call('GET', '/tagged/1');
        $this->assertRegularResponse($thirdResponse);
        $this->assertDifferentResponse($secondResponse, $thirdResponse);
    }

    /** @test */
    public function it_can_forget_requests_using_route_cache_tags_from_global_cache()
    {
        $firstResponse = $this->call('GET', '/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $this->app['cache']->store(config('responsecache.cache_store'))->tags('laravel-responsecache')->clear('foo');

        $secondResponse = $this->call('GET', '/tagged/1');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_can_forget_requests_using_multiple_route_cache_tags()
    {
        $firstResponse = $this->call('GET', '/tagged/2');
        $this->assertRegularResponse($firstResponse);

        $this->app['responsecache']->clear(['bar']);

        $secondResponse = $this->call('GET', '/tagged/2');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }
}
