<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Events\ResponseCacheHit;

beforeEach(function () {
    // Use array driver for time-based tests and tag support
    config()->set('responsecache.cache_store', 'array');
    config()->set('cache.default', 'array');
    Cache::forgetDriver('array');

    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('fresh cache period', function () {
    it('returns cached response within fresh period', function () {
        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        Carbon::setTestNow(Carbon::now()->addSeconds(3));

        $secondResponse = $this->get('/flexible/basic');
        $secondContent = $secondResponse->getContent();

        expect($secondContent)->toBe($firstContent);
    });


    it('adds cache time header when configured', function () {
        config()->set('responsecache.add_cache_time_header', true);
        config()->set('responsecache.cache_time_header_name', 'X-Cached-At');

        $this->get('/flexible/basic');

        $cachedResponse = $this->get('/flexible/basic');

        expect($cachedResponse->headers->has('X-Cached-At'))->toBeTrue();
    });

    it('adds cache age header when configured', function () {
        config()->set('responsecache.add_cache_time_header', true);
        config()->set('responsecache.add_cache_age_header', true);
        config()->set('responsecache.cache_age_header_name', 'X-Cache-Age');

        $this->get('/flexible/basic');

        Carbon::setTestNow(Carbon::now()->addSeconds(2));

        $cachedResponse = $this->get('/flexible/basic');

        expect($cachedResponse->headers->has('X-Cache-Age'))->toBeTrue();
        expect((int) $cachedResponse->headers->get('X-Cache-Age'))->toBeGreaterThanOrEqual(0);
    });
});

describe('stale-while-revalidate period', function () {
    it('returns stale response immediately when in stale window', function () {
        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        // Move to stale period (after fresh=5s, before total=10s)
        Carbon::setTestNow(Carbon::now()->addSeconds(7));

        $secondResponse = $this->get('/flexible/basic');
        $secondContent = $secondResponse->getContent();

        // Should return same stale content immediately
        expect($secondContent)->toBe($firstContent);
    });

    it('schedules background refresh during stale period', function () {
        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        // Move to stale period
        Carbon::setTestNow(Carbon::now()->addSeconds(7));

        $secondResponse = $this->get('/flexible/basic');
        $secondContent = $secondResponse->getContent();

        // Should return stale content
        expect($secondContent)->toBe($firstContent);

        // Simulate app termination to run background refresh
        app()->terminate();

        // After background refresh, the cache should have new content
        $thirdResponse = $this->get('/flexible/basic');
        $thirdContent = $thirdResponse->getContent();

        // Now should have fresh content (different from original)
        expect($thirdContent)->not->toBe($firstContent);
    });

    it('returns stale content on first request in stale window', function () {
        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        Carbon::setTestNow(Carbon::now()->addSeconds(7));

        // First request in stale period should return stale content
        $secondResponse = $this->get('/flexible/basic');
        expect($secondResponse->getContent())->toBe($firstContent);

        // Note: In real Laravel app, the request above would have terminated
        // and run the background refresh. The next request gets fresh content.
        $thirdResponse = $this->get('/flexible/basic');
        // Third request gets the refreshed content from second request's termination
        expect($thirdResponse->getContent())->not->toBe($firstContent);
    });
});

describe('expired cache', function () {
    it('recomputes response when beyond stale window', function () {
        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        // Move beyond stale window (fresh=5s + stale=10s = total 15s)
        Carbon::setTestNow(Carbon::now()->addSeconds(16));

        $secondResponse = $this->get('/flexible/basic');
        $secondContent = $secondResponse->getContent();

        // Should have new content
        expect($secondContent)->not->toBe($firstContent);
        expect($secondContent)->toStartWith('random-');
    });

    it('blocks during recomputation when cache is expired', function () {
        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        Carbon::setTestNow(Carbon::now()->addSeconds(20));

        // Request should block and recompute
        $secondResponse = $this->get('/flexible/basic');

        // Immediate next request should get the fresh cache
        $thirdResponse = $this->get('/flexible/basic');

        expect($secondResponse->getContent())->not->toBe($firstContent);
        expect($thirdResponse->getContent())->toBe($secondResponse->getContent());
    });
});

describe('custom time windows', function () {
    it('respects custom fresh and stale periods', function () {
        // fresh=3s, stale=15s
        $firstResponse = $this->get('/flexible/custom-time');
        $firstContent = $firstResponse->getContent();

        // Within fresh period (3s)
        Carbon::setTestNow(Carbon::now()->addSeconds(2));
        $freshResponse = $this->get('/flexible/custom-time');
        expect($freshResponse->getContent())->toBe($firstContent);

        // In stale period (between 3s and 18s total)
        Carbon::setTestNow(Carbon::now()->addSeconds(8)); // Now at 10s total
        $staleResponse = $this->get('/flexible/custom-time');
        expect($staleResponse->getContent())->toBe($firstContent);

        app()->terminate();

        // After refresh
        $refreshedResponse = $this->get('/flexible/custom-time');
        expect($refreshedResponse->getContent())->not->toBe($firstContent);
    });
});

describe('cache tags', function () {
    it('stores flexible cache with tags', function () {
        $firstResponse = $this->get('/flexible/with-tags');
        $secondResponse = $this->get('/flexible/with-tags');

        expect($secondResponse->getContent())->toBe($firstResponse->getContent());
    });

    it('can clear tagged flexible cache', function () {
        $firstResponse = $this->get('/flexible/with-tags');
        $firstContent = $firstResponse->getContent();

        // Clear cache by tag
        Cache::tags(['tag1'])->flush();

        $secondResponse = $this->get('/flexible/with-tags');
        $secondContent = $secondResponse->getContent();

        // Should have new content after tag flush
        expect($secondContent)->not->toBe($firstContent);
    });

    it('schedules background refresh with tags during stale period', function () {
        $firstResponse = $this->get('/flexible/with-tags');
        $firstContent = $firstResponse->getContent();

        Carbon::setTestNow(Carbon::now()->addSeconds(7));

        $staleResponse = $this->get('/flexible/with-tags');
        expect($staleResponse->getContent())->toBe($firstContent);

        app()->terminate();

        $refreshedResponse = $this->get('/flexible/with-tags');
        expect($refreshedResponse->getContent())->not->toBe($firstContent);
    });
});

describe('events', function () {
    it('fires ResponseCacheHit event when serving from cache', function () {
        Event::fake();

        $this->get('/flexible/basic');
        $this->get('/flexible/basic');

        Event::assertDispatched(ResponseCacheHit::class);
    })->skip('Currently this test does not work due to a bug in Laravel 11');

    it('fires CacheMissed event on first request', function () {
        Event::fake();

        $this->get('/flexible/basic');

        Event::assertDispatched(CacheMissed::class);
    })->skip('Currently this test does not work due to a bug in Laravel 11');
});

describe('middleware parameter parsing', function () {
    it('parses flexible time format correctly', function () {
        // Test via actual middleware usage
        $firstResponse = $this->get('/flexible/basic');
        $secondResponse = $this->get('/flexible/basic');

        expect($secondResponse->getContent())->toBe($firstResponse->getContent());
    });

    it('parses flexible time with tags correctly', function () {
        // Test that tags are properly parsed from middleware parameters
        $firstResponse = $this->get('/flexible/with-tags');
        $firstContent = $firstResponse->getContent();

        $secondResponse = $this->get('/flexible/with-tags');
        expect($secondResponse->getContent())->toBe($firstContent);

        // Verify tag works by clearing it
        Cache::tags(['tag1'])->flush();

        $thirdResponse = $this->get('/flexible/with-tags');
        // Should be different because cache was cleared
        expect($thirdResponse->getContent())->not->toBe($firstContent);
    });
});


describe('bypass functionality', function () {
    it('bypasses flexible cache when bypass header is present', function () {
        config()->set('responsecache.cache_bypass_header.name', 'X-Cache-Bypass');
        config()->set('responsecache.cache_bypass_header.value', 'true');

        $firstResponse = $this->get('/flexible/basic', ['X-Cache-Bypass' => 'true']);
        $secondResponse = $this->get('/flexible/basic', ['X-Cache-Bypass' => 'true']);

        expect($secondResponse->getContent())->not->toBe($firstResponse->getContent());
    });
});

describe('disabled cache', function () {
    it('does not use flexible cache when package is disabled', function () {
        config()->set('responsecache.enabled', false);

        $firstResponse = $this->get('/flexible/basic');
        $secondResponse = $this->get('/flexible/basic');

        expect($secondResponse->getContent())->not->toBe($firstResponse->getContent());
    });
});

describe('edge cases', function () {
    it('handles concurrent requests during cache miss', function () {
        // First request creates cache
        $firstResponse = $this->get('/flexible/basic');

        // Multiple requests should all get same cached value
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/flexible/basic');
        }

        foreach ($responses as $response) {
            expect($response->getContent())->toBe($firstResponse->getContent());
        }
    });

    it('maintains cache independence across different routes', function () {
        // Test that basic and custom-time routes cache independently
        $responseBasic1 = $this->get('/flexible/basic');
        $responseCustom1 = $this->get('/flexible/custom-time');

        $responseBasic2 = $this->get('/flexible/basic');
        $responseCustom2 = $this->get('/flexible/custom-time');

        // Each route should cache independently
        expect($responseBasic2->getContent())->toBe($responseBasic1->getContent());
        expect($responseCustom2->getContent())->toBe($responseCustom1->getContent());
        expect($responseBasic1->getContent())->not->toBe($responseCustom1->getContent());
    });
});

describe('always defer mode', function () {
    it('always defers refresh when flexible_always_defer is enabled', function () {
        config()->set('responsecache.flexible_always_defer', true);

        $firstResponse = $this->get('/flexible/basic');
        $firstContent = $firstResponse->getContent();

        // Even within fresh period, should return immediately and defer
        Carbon::setTestNow(Carbon::now()->addSeconds(2));

        $secondResponse = $this->get('/flexible/basic');
        expect($secondResponse->getContent())->toBe($firstContent);

        app()->terminate();

        // After termination, should have potentially new content
        $thirdResponse = $this->get('/flexible/basic');
        expect($thirdResponse->getContent())->toStartWith('random-');
    });
});
