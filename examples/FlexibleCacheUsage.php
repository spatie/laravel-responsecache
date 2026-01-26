<?php

/**
 * Example: Flexible Cache / Stale-While-Revalidate (SWR)
 *
 * Flexible caching allows you to serve stale content while refreshing in the background,
 * providing better performance and user experience for dynamic content.
 */

use App\Http\Controllers\Controller;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;

// ============================================================================
// OPTION 1: Using Attributes (Recommended)
// ============================================================================

class ApiController extends Controller
{
    /**
     * Flexible caching with attributes
     *
     * - Fresh: 60 seconds - During this time, cached response is served
     * - Stale: 300 seconds - After fresh period, serve stale content while refreshing
     * - Defer: false - Refresh happens synchronously on first request after fresh period
     */
    #[FlexibleCache(fresh: 60, stale: 300, defer: false, tags: ['api', 'stats'])]
    public function stats()
    {
        // This expensive operation only runs:
        // 1. When cache is empty
        // 2. After fresh period (60s) on the first request
        $stats = [
            'users' => User::count(),
            'posts' => Post::count(),
            'views' => Analytics::getTotalViews(),
        ];

        return response()->json($stats);
    }

    /**
     * With deferred refresh enabled
     *
     * - Defer: true - Refresh happens in background, stale content served immediately
     */
    #[FlexibleCache(fresh: 120, stale: 600, defer: true, tags: ['api'])]
    public function recommendations()
    {
        // Even after fresh period, users always get instant response
        // Refresh happens in background queue
        $recommendations = RecommendationEngine::generate();

        return response()->json($recommendations);
    }
}

// ============================================================================
// OPTION 2: Using Middleware in Routes
// ============================================================================

// Basic flexible cache
Route::get('/api/live-data', function () {
    return response()->json([
        'timestamp' => now(),
        'data' => expensiveOperation(),
    ]);
})->middleware(FlexibleCacheResponse::for(
    fresh: 30,
    stale: 180,
    defer: false
));

// With CarbonInterval for better readability
Route::get('/api/dashboard', function () {
    return response()->json(Dashboard::getData());
})->middleware(FlexibleCacheResponse::for(
    fresh: CarbonInterval::minutes(5),
    stale: CarbonInterval::minutes(30),
    defer: true,
    tags: ['dashboard']
));

// ============================================================================
// Real-World Example: Blog with Different Caching Strategies
// ============================================================================

class BlogController extends Controller
{
    /**
     * Homepage: Flexible cache with short fresh period
     * Visitors see fresh content most of the time, but during high traffic
     * stale content is served while refreshing in background
     */
    #[FlexibleCache(fresh: 60, stale: 300, defer: true, tags: ['blog', 'homepage'])]
    public function home()
    {
        return view('blog.home', [
            'posts' => Post::latest()->take(10)->get(),
            'featured' => Post::featured()->first(),
        ]);
    }

    /**
     * Individual post: Standard cache for 10 minutes
     * Posts don't change often, so simple TTL caching is fine
     */
    #[Cache(lifetime: 600, tags: ['blog', 'posts'])]
    public function show(Post $post)
    {
        return view('blog.show', compact('post'));
    }

    /**
     * Trending posts: Flexible cache with longer stale period
     * Trending calculation is expensive, but it's okay to show slightly stale data
     */
    #[FlexibleCache(fresh: 300, stale: 1800, defer: true, tags: ['blog', 'trending'])]
    public function trending()
    {
        return view('blog.trending', [
            'posts' => Post::trending()->take(20)->get(),
        ]);
    }
}

// ============================================================================
// Understanding the Parameters
// ============================================================================

/*
 * fresh: How long the cached content is considered "fresh"
 *        During this period, cached response is served without any checks
 *
 * stale: Additional time after fresh period to serve stale content while refreshing
 *        Total cache lifetime = fresh + stale
 *
 * defer: Whether to refresh in background
 *        - false: First request after fresh period waits for refresh
 *        - true: First request after fresh period gets stale content, refresh happens in queue
 *
 * tags: Cache tags for selective invalidation
 *       Example: Clear all blog cache with responsecache:clear --tags=blog
 */

// ============================================================================
// Clearing Flexible Cache
// ============================================================================

// Clear all cache
php artisan responsecache:clear

// Clear specific tags
php artisan responsecache:clear --tags=blog,api

// Programmatic clearing
app(ResponseCache::class)->clear(['blog', 'trending']);
