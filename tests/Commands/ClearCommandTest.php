<?php

use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\ResponseCache\Events\ClearedResponseCacheEvent;
use Spatie\ResponseCache\Events\ClearingResponseCacheEvent;
use Spatie\ResponseCache\ResponseCacheRepository;

use function PHPUnit\Framework\assertNull;

beforeEach(function () {
    $this->createTaggableResponseCacheStore = function ($tag): Repository {
        $this->app['config']->set('responsecache.cache.store', 'array');
        $this->app['config']->set('responsecache.cache.tag', $tag);

        // Simulating construction of Repository inside of the service provider
        return $this->app->contextual[ResponseCacheRepository::class][$this->app->getAlias(Repository::class)]();
    };
});

it('will clear the cache', function () {
    $firstResponse = $this->get('/random');

    Artisan::call('responsecache:clear');

    $secondResponse = $this->get('/random');

    assertRegularResponse($firstResponse);
    assertRegularResponse($secondResponse);

    assertDifferentResponse($firstResponse, $secondResponse);
});

it('will clear only one page from cache', function () {
    $firstResponse = $this->get('/random/1');
    $firstAlternativeResponse = $this->get('/random/2');

    Artisan::call('responsecache:clear --url=/random/1');

    $secondResponse = $this->get('/random/1');
    $secondAlternativeResponse = $this->get('/random/2');

    assertRegularResponse($firstResponse);
    assertRegularResponse($secondResponse);
    assertDifferentResponse($firstResponse, $secondResponse);

    assertRegularResponse($firstAlternativeResponse);
    assertCachedResponse($secondAlternativeResponse);
    assertSameResponse($firstAlternativeResponse, $secondAlternativeResponse);
});

it('will fire events when clearing the cache', function () {
    $this->app['config']->set('responsecache.cache.store', 'array');
    $this->app->forgetInstance('responsecache');

    Event::fake();

    Artisan::call('responsecache:clear');

    Event::assertDispatched(ClearingResponseCacheEvent::class);
    Event::assertDispatched(ClearedResponseCacheEvent::class);
});

it('will clear all when tags are not defined', function () {
    $responseCache = ($this->createTaggableResponseCacheStore)(null);
    $appCache = $this->app['cache']->store('array');

    $appCache->forever('appData', 'someValue');
    $responseCache->clear();

    assertNull($appCache->get('appData'));
});
