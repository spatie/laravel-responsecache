<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Middlewares\CacheResponse;

use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    Route::any('/flexible-cache', function () {
        return response()->json([
            'time' => now()->toDateTimeString(),
            'random' => Str::random(10),
        ]);
    })->middleware(CacheResponse::flexible(10, 20));

    Route::any('/flexible-cache-profile', function () {
        return response()->json([
            'time' => now()->toDateTimeString(),
            'random' => Str::random(10),
        ]);
    });
});

it('will cache response using flexible cache middleware', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.add_cache_freshness_header', true);

    $firstResponse = $this->get('/flexible-cache');
    $secondResponse = $this->get('/flexible-cache');

    assertTrue($secondResponse->headers->has('laravel-responsecache'));
    assertTrue($secondResponse->headers->has('laravel-responsecache-freshness'));

    $this->assertEquals('flexible', $secondResponse->headers->get('laravel-responsecache-freshness'));

    assertSameResponse($firstResponse, $secondResponse);
});

it('will serve stale cache when fresh period expires', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.add_cache_freshness_header', true);

    Carbon::setTestNow(Carbon::now());

    $firstResponse = $this->get('/flexible-cache');
    $this->assertEquals(
        Carbon::now()->toRfc2822String(),
        $firstResponse->headers->get('laravel-responsecache')
    );

    Carbon::setTestNow(Carbon::now()->addSeconds(11));

    $secondResponse = $this->get('/flexible-cache');

    assertTrue($secondResponse->headers->has('laravel-responsecache'));
    assertTrue($secondResponse->headers->has('laravel-responsecache-freshness'));

    $this->assertEquals('flexible', $secondResponse->headers->get('laravel-responsecache-freshness'));

    assertSameResponse($firstResponse, $secondResponse);

    Carbon::setTestNow();
});

it('will not serve cache when stale period expires', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.add_cache_freshness_header', true);

    Carbon::setTestNow(Carbon::now());

    $firstResponse = $this->get('/flexible-cache');

    Carbon::setTestNow(Carbon::now()->addSeconds(31));

    $secondResponse = $this->get('/flexible-cache');

    assertDifferentResponse($firstResponse, $secondResponse);

    Carbon::setTestNow();
});

it('will add cache age header for flexible cache', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.add_cache_age_header', true);
    $this->app['config']->set('responsecache.cache_age_header_name', 'X-Cache-Age');

    Carbon::setTestNow(Carbon::now());

    $this->get('/flexible-cache');

    Carbon::setTestNow(Carbon::now()->addSeconds(5));

    $secondResponse = $this->get('/flexible-cache');

    assertTrue($secondResponse->headers->has('X-Cache-Age'));

    $cacheAge = (int) $secondResponse->headers->get('X-Cache-Age');

    $this->assertGreaterThanOrEqual(4, $cacheAge);
    $this->assertLessThanOrEqual(6, $cacheAge);

    Carbon::setTestNow();
});

it('can use flexible cache through cache profile', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.add_cache_freshness_header', true);
    $this->app['config']->set('responsecache.flexible_cache_enabled', true);
    $this->app['config']->set('responsecache.flexible_cache_time', [10, 20]);

    Route::middleware(CacheResponse::class)->group(function () {
        Route::any('/profile-flexible', function () {
            return response()->json([
                'time' => now()->toDateTimeString(),
                'random' => Str::random(10),
            ]);
        });
    });

    $firstResponse = $this->get('/profile-flexible');
    $secondResponse = $this->get('/profile-flexible');

    assertTrue($secondResponse->headers->has('laravel-responsecache-freshness'));

    assertSameResponse($firstResponse, $secondResponse);
});

it('will handle always defer option correctly', function () {
    $this->app['config']->set('responsecache.add_cache_time_header', true);
    $this->app['config']->set('responsecache.flexible_always_defer', true);

    Route::any('/always-defer', function () {
        return response()->json([
            'time' => now()->toDateTimeString(),
            'random' => Str::random(10),
        ]);
    })->middleware(CacheResponse::flexible(10, 20));

    $firstResponse = $this->get('/always-defer');
    $secondResponse = $this->get('/always-defer');

    assertSameResponse($firstResponse, $secondResponse);
});
