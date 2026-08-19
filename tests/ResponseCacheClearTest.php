<?php

use Illuminate\Support\Facades\Event;
use JMac\Testing\Double;
use Mockery\MockInterface;
use Spatie\ResponseCache\Events\ClearedResponseCacheEvent;
use Spatie\ResponseCache\Events\ClearingResponseCacheEvent;
use Spatie\ResponseCache\Events\ClearingResponseCacheFailedEvent;
use Spatie\ResponseCache\ResponseCache;
use Spatie\ResponseCache\ResponseCacheRepository;

beforeEach(function () {
    Event::fake();
});

it('will fire appropriate events when clearing the cache successfully', function () {
    $mock = Double::for(ResponseCacheRepository::class);
    $mock->expects('clear')->returns(true);
    $this->swap(ResponseCacheRepository::class, $mock);

    $result = app(ResponseCache::class)->clear();

    expect($result)->toBeTrue();
    Event::assertDispatched(ClearingResponseCacheEvent::class);
    Event::assertDispatched(ClearedResponseCacheEvent::class);
    Event::assertNotDispatched(ClearingResponseCacheFailedEvent::class);
});

it('will fire appropriate events when clearing the cache fails', function () {
    $mock = Double::for(ResponseCacheRepository::class);
    $mock->expects('clear')->returns(false);
    $this->swap(ResponseCacheRepository::class, $mock);

    $result = app(ResponseCache::class)->clear();

    expect($result)->toBeFalse();
    Event::assertDispatched(ClearingResponseCacheEvent::class);
    Event::assertNotDispatched(ClearedResponseCacheEvent::class);
    Event::assertDispatched(ClearingResponseCacheFailedEvent::class);
});
