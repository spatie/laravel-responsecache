<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Request;

class ResponseCacheTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function it_can_cache_a_request()
    {
        $this->assertRegularResponse($this->call('GET', '/'));
        $this->assertCachedResponse($this->call('GET', '/'));
    }
}
