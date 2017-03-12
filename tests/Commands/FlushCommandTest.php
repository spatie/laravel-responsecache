<?php

namespace Spatie\ResponseCache\Test\Commands;

use Illuminate\Support\Facades\Artisan;
use Spatie\ResponseCache\Test\TestCase;

class FlushCommandTest extends TestCase
{
    /** @test */
    public function it_will_clear_the_cache()
    {
        $firstResponse = $this->call('GET', '/random');

        Artisan::call('responsecache:flush');

        $secondResponse = $this->call('GET', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }
}
