<?php

namespace Spatie\ResponseCache\Test\CacheProfiles;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\ResponseCacheConfig;
use Spatie\ResponseCache\Test\TestCase;
use Spatie\ResponseCache\Test\User;

class CacheAllSuccessfulGetRequestsTest extends TestCase
{
    protected CacheAllSuccessfulGetRequests $cacheProfile;

    /**
     * @throws InvalidConfig
     */
    public function setUp(): void
    {
        parent::setUp();

        $config = $this->getConfig();
        $config['cache_profile'] = CacheAllSuccessfulGetRequests::class;
        app()->instance(ResponseCacheConfig::class, new ResponseCacheConfig($config));

        $this->cacheProfile = app(CacheAllSuccessfulGetRequests::class);
    }

    /** @test */
    public function it_will_determine_that_get_requests_should_be_cached()
    {
        $request = $this->createRequest('get');
        $config = app(ResponseCacheConfig::class);
        $this->assertTrue($this->cacheProfile->shouldCacheRequest($request, $config));
    }

    /** @test */
    public function it_will_determine_that_all_non_get_request_should_not_be_cached()
    {
        $config = app(ResponseCacheConfig::class);
        $this->assertFalse($this->cacheProfile->shouldCacheRequest($this->createRequest('post'), $config));
        $this->assertFalse($this->cacheProfile->shouldCacheRequest($this->createRequest('patch'), $config));
        $this->assertFalse($this->cacheProfile->shouldCacheRequest($this->createRequest('delete'), $config));
    }

    /** @test */
    public function it_will_determine_that_a_successful_response_should_be_cached()
    {
        $config = app(ResponseCacheConfig::class);
        foreach (range(200, 399) as $statusCode) {
            $this->assertTrue($this->cacheProfile->shouldCacheResponse($this->createResponse($statusCode), $config));
        }
    }

    /** @test */
    public function it_will_determine_that_a_non_text_response_should_not_be_cached()
    {
        $config = app(ResponseCacheConfig::class);

        $response = $this->createResponse(200, 'application/pdf');

        $shouldCacheResponse = $this->cacheProfile->shouldCacheResponse($response, $config);

        $this->assertFalse($shouldCacheResponse);
    }

    /** @test */
    public function it_will_determine_that_a_json_response_should_be_cached()
    {
        $config = app(ResponseCacheConfig::class);

        $response = new JsonResponse(['a' => 'b']);

        $shouldCacheResponse = $this->cacheProfile->shouldCacheResponse($response, $config);

        $this->assertTrue($shouldCacheResponse);
    }

    /** @test */
    public function it_will_determine_that_an_error_should_not_be_cached()
    {
        $config = app(ResponseCacheConfig::class);

        foreach (range(400, 599) as $statusCode) {
            $this->assertFalse($this->cacheProfile->shouldCacheResponse($this->createResponse($statusCode), $config));
        }
    }

    /** @test */
    public function it_will_use_the_id_of_the_logged_in_user_to_differentiate_caches()
    {
        $config = app(ResponseCacheConfig::class);

        $this->assertEquals('', $this->cacheProfile->useCacheNameSuffix($this->createRequest('get'), $config));

        User::all()->map(function ($user) use ($config) {
            auth()->login(User::find($user->id));
            $this->assertEquals($user->id,
                $this->cacheProfile->useCacheNameSuffix($this->createRequest('get'), $config));
        });
    }

    /** @test */
    public function it_will_determine_to_cache_responses_for_a_certain_amount_of_time()
    {
        $config = app(ResponseCacheConfig::class);

        /** @var Carbon $expirationDate */
        $expirationDate = $this->cacheProfile->cacheRequestUntil($this->createRequest('get'), $config);

        $this->assertTrue($expirationDate->isFuture());
    }

    /**
     * Create a new request with the given method.
     *
     * @param $method
     *
     * @return Request
     */
    protected function createRequest($method): Request
    {
        $request = new Request();

        $request->setMethod($method);

        return $request;
    }

    /**
     * Create a new response with the given statusCode.
     *
     * @param  int  $statusCode
     * @param  string  $contentType
     * @return Response
     */
    protected function createResponse(int $statusCode = 200, string $contentType = 'text/html; charset=UTF-8'): Response
    {
        $response = new Response();

        $response
            ->setStatusCode($statusCode)
            ->headers->set('Content-Type', $contentType);

        return $response;
    }
}
