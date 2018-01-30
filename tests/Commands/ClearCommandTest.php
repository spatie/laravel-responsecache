<?php

namespace Spatie\ResponseCache\Test\Commands;

use Event;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Artisan;
use Spatie\ResponseCache\Test\TestCase;
use Spatie\ResponseCache\ResponseCacheRepository;
use Spatie\ResponseCache\Events\ClearedResponseCache;
use Spatie\ResponseCache\Events\FlushedResponseCache;
use Spatie\ResponseCache\Events\ClearingResponseCache;
use Spatie\ResponseCache\Events\FlushingResponseCache;

class ClearCommandTest extends TestCase
{
    /** @test */
    public function it_will_clear_the_cache()
    {
        $firstResponse = $this->call('GET', '/random');

        Artisan::call('responsecache:clear');

        $secondResponse = $this->call('GET', '/random');

        $this->assertRegularResponse($firstResponse);
        $this->assertRegularResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }

    /** @test */
    public function it_will_fire_events_when_clearing_the_cache()
    {
        Event::fake();

        Artisan::call('responsecache:clear');

        Event::assertDispatched(FlushingResponseCache::class);
        Event::assertDispatched(ClearingResponseCache::class);
        Event::assertDispatched(FlushedResponseCache::class);
        Event::assertDispatched(ClearedResponseCache::class);
    }

    /** @test */
    public function it_will_clear_all_when_tags_are_not_defined()
    {
        $responseCache = $this->createTaggableResponseCacheStore(null);
        $appCache = $this->app['cache']->store('array');

        $appCache->forever('appData', 'someValue');
        $responseCache->clear();

        $this->assertNull($appCache->get('appData'));
    }

    protected function createTaggableResponseCacheStore($tag): Repository
    {
        $this->app['config']->set('responsecache.cache_store', 'array');
        $this->app['config']->set('responsecache.cache_tag', $tag);

        // Simulating construction of Repository inside of the service provider
        return $this->app->contextual[ResponseCacheRepository::class][$this->app->getAlias(Repository::class)]();
    }
}
