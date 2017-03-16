<?php

namespace Spatie\ResponseCache\Test\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\ResponseCache\Events\FlushedResponseCache;
use Spatie\ResponseCache\Events\FlushingResponseCache;
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

    /** @test */
    public function it_will_fire_events_when_clearing_the_cache()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['callback'])->getMock();

        $mock->expects($this->exactly(2))
            ->method('callback');

        Event::listen(FlushingResponseCache::class, function () use ($mock) {
            $mock->callback();
        });

        Event::listen(FlushedResponseCache::class, function () use ($mock) {
            $mock->callback();
        });

        Artisan::call('responsecache:flush');
    }
}
