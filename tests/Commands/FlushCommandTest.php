<?php

namespace Spatie\ResponseCache\Test\Commands;

use Event;
use Illuminate\Support\Facades\Artisan;
use Spatie\ResponseCache\Test\TestCase;
use Spatie\ResponseCache\Events\FlushedResponseCache;
use Spatie\ResponseCache\Events\FlushingResponseCache;

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

    /** @test */
    public function it_will_fire_events_when_clearing_the_cache()
    {
        Event::fake();

        Artisan::call('responsecache:flush');

        Event::assertDispatched(FlushingResponseCache::class);
        Event::assertDispatched(FlushedResponseCache::class);
    }
}
