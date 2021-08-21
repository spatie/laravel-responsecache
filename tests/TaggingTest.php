<?php

namespace Spatie\ResponseCache\Test;

use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\ResponseCacheConfig;

class TaggingTest extends TestCase
{
    private ResponseCacheConfig $cacheConfig;

    /**
     * @throws InvalidConfig
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Set the driver to array (tags don't work with the file driver)
        $config = $this->getConfig();
        $config['cache_store'] = 'array';
        $config['cache_tag'] = 'tagging-test';
        $this->cacheConfig = new ResponseCacheConfig($config);
        app()->instance(ResponseCacheConfig::class, $this->cacheConfig);
    }

    /** @test */
    public function it_can_cache_requests_using_route_cache_tags()
    {
        $firstResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $secondResponse = $this->get('/tagged/1');

        $this->assertCachedResponse($secondResponse);
        $this->assertSameResponse($firstResponse, $secondResponse);

        $thirdResponse = $this->get('/tagged/2');
        $this->assertRegularResponse($thirdResponse);

        $fourthResponse = $this->get('/tagged/2');
        $this->assertCachedResponse($fourthResponse);
        $this->assertSameResponse($thirdResponse, $fourthResponse);
    }

    /** @test */
    public function it_can_forget_requests_using_route_cache_tags()
    {
        $firstResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $this->app['responsecache']->clear(['foo']);

        $secondResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);

        $this->app['responsecache']->clear();

        $thirdResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($thirdResponse);
        $this->assertDifferentResponse($secondResponse, $thirdResponse);
    }

    /** @test */
    public function it_can_forget_requests_using_route_cache_tags_from_global_cache()
    {
        $firstResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $this->app['cache']->store($this->cacheConfig->cache_store)->tags('laravel-responsecache')->clear('foo');

        $secondResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_can_forget_requests_using_route_cache_tags_without_deleting_unrelated_cache()
    {
        $this->app['cache']->store($this->cacheConfig->cache_store)->tags('unrelated-cache')->put('baz', true);

        $firstResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($firstResponse);

        $this->app['responsecache']->clear();

        $secondResponse = $this->get('/tagged/1');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);

        $cacheValue = $this->app['cache']->store($this->cacheConfig->cache_store)->tags('unrelated-cache')->get('baz');
        self::assertThat($cacheValue, self::isTrue(), 'Failed to assert that a cached value is present');
    }

    /** @test */
    public function it_can_forget_requests_using_multiple_route_cache_tags()
    {
        $firstResponse = $this->get('/tagged/2');
        $this->assertRegularResponse($firstResponse);

        $this->app['responsecache']->clear(['bar']);

        $secondResponse = $this->get('/tagged/2');
        $this->assertRegularResponse($secondResponse);
        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

}
