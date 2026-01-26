<?php

namespace App\Cache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\BaseCacheProfile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Example: Custom Cache Profile
 *
 * Create custom cache profiles to control which requests should be cached
 * and for how long based on your application's specific needs.
 */

/**
 * Cache only authenticated user requests with user-specific cache
 */
class CacheAuthenticatedRequests extends BaseCacheProfile
{
    public function shouldCacheRequest(Request $request): bool
    {
        // Don't cache requests from console
        if ($this->isRunningInConsole()) {
            return false;
        }

        // Only cache for authenticated users
        if (! auth()->check()) {
            return false;
        }

        // Only cache GET and HEAD requests
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        return true;
    }

    public function shouldCacheResponse(Response $response): bool
    {
        // Only cache successful responses
        if (! $response->isSuccessful()) {
            return false;
        }

        return true;
    }

    public function useCacheNameSuffix(Request $request): string
    {
        // Create separate cache for each user
        return 'user-' . auth()->id();
    }
}

/**
 * Cache API requests with longer TTL for specific endpoints
 */
class ApiCacheProfile extends BaseCacheProfile
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($this->isRunningInConsole()) {
            return false;
        }

        // Only cache GET requests
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Don't cache requests with query parameters (except 'page')
        $queryParams = array_keys($request->query());
        $allowedParams = ['page'];
        $hasDisallowedParams = ! empty(array_diff($queryParams, $allowedParams));

        if ($hasDisallowedParams) {
            return false;
        }

        return true;
    }

    public function shouldCacheResponse(Response $response): bool
    {
        if (! $response->isSuccessful()) {
            return false;
        }

        // Only cache JSON responses
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'json')) {
            return false;
        }

        return true;
    }

    public function cacheRequestUntil(Request $request): \DateTime
    {
        // Cache reference data endpoints longer
        if (str_starts_with($request->path(), 'api/reference/')) {
            return now()->addDay();
        }

        // Cache list endpoints for 5 minutes
        if (str_starts_with($request->path(), 'api/')) {
            return now()->addMinutes(5);
        }

        // Default: 1 hour
        return now()->addHour();
    }
}

/**
 * Don't cache for admin users
 */
class CacheExceptAdmins extends BaseCacheProfile
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($this->isRunningInConsole()) {
            return false;
        }

        // Don't cache for admin users
        if (auth()->check() && auth()->user()->isAdmin()) {
            return false;
        }

        // Only GET requests
        if (! $request->isMethod('GET')) {
            return false;
        }

        return true;
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful();
    }
}

/**
 * Cache based on user's subscription tier
 */
class TieredCacheProfile extends BaseCacheProfile
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($this->isRunningInConsole()) {
            return false;
        }

        return $request->isMethod('GET');
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful();
    }

    public function cacheRequestUntil(Request $request): \DateTime
    {
        if (! auth()->check()) {
            // Free users: cache for 1 hour
            return now()->addHour();
        }

        $user = auth()->user();

        // Premium users: cache for 5 minutes (more up-to-date data)
        if ($user->isPremium()) {
            return now()->addMinutes(5);
        }

        // Regular users: cache for 30 minutes
        return now()->addMinutes(30);
    }

    public function useCacheNameSuffix(Request $request): string
    {
        if (! auth()->check()) {
            return 'guest';
        }

        // Separate cache per user and their tier
        return auth()->id() . '-' . auth()->user()->tier;
    }
}

// ============================================================================
// Registering Your Custom Profile
// ============================================================================

/*
 * In config/responsecache.php:
 *
 * 'cache_profile' => App\Cache\ApiCacheProfile::class,
 */

// ============================================================================
// Testing Your Cache Profile
// ============================================================================

/*
 * Create a test to verify your cache profile logic:
 */

namespace Tests\Feature;

use Tests\TestCase;

class CacheProfileTest extends TestCase
{
    public function test_authenticated_requests_are_cached()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSuccessful();

        // Second request should hit cache
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertHeader('X-Response-Cache-Status', 'HIT');
    }

    public function test_guest_requests_are_not_cached()
    {
        $this->get('/dashboard')
            ->assertSuccessful();

        // Should not be cached
        $this->get('/dashboard')
            ->assertHeaderMissing('X-Response-Cache-Status');
    }
}
