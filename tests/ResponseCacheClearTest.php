<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Spatie\ResponseCache\Events\ClearedResponseCache;
use Spatie\ResponseCache\Events\ClearingResponseCache;
use Spatie\ResponseCache\Events\ClearingResponseCacheFailed;
use Spatie\ResponseCache\ResponseCache;
use Spatie\ResponseCache\ResponseCacheRepository;

beforeEach(function () {
    Event::fake();
});

it('will fire appropriate events when clearing the cache successfully', function () {
    $result = app(ResponseCache::class)->clear();

    expect($result)->toBeTrue();
    Event::assertDispatched(ClearingResponseCache::class);
    Event::assertDispatched(ClearedResponseCache::class);
    Event::assertNotDispatched(ClearingResponseCacheFailed::class);
});

it('will fire appropriate events when clearing the cache fails', function () {
    $this->mock(ResponseCacheRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('clear')
            ->once()
            ->andReturn(false);
    });

    $result = app(ResponseCache::class)->clear();

    expect($result)->toBeFalse();
    Event::assertDispatched(ClearingResponseCache::class);
    Event::assertNotDispatched(ClearedResponseCache::class);
    Event::assertDispatched(ClearingResponseCacheFailed::class);
});
