<?php

namespace Spatie\ResponseCache\Test;

class ResponseCacheTest extends TestCase
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
        $this->assertRegularResponse($this->call('GET', '/'));
        $this->assertCachedResponse($this->call('GET', '/'));
    }

    /**
     * @test
     */
    public function it_will_not_cache_a_post_request()
    {
        $this->assertRegularResponse($this->call('POST', '/'));
        $this->assertRegularResponse($this->call('POST', '/'));
    }
}
