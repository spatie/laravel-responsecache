<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

use function PHPUnit\Framework\isFalse;
use function PHPUnit\Framework\isTrue;

use Spatie\ResponseCache\Test\TestCase;
use Symfony\Component\HttpFoundation\Response;

uses(TestCase::class)->in('.');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function assertCachedResponse(TestResponse $response)
{
    test()->assertThat($response->headers->has('laravel-responsecache'), isTrue(), 'Failed to assert that the response has been cached');
}

function assertRegularResponse(TestResponse $response)
{
    test()->assertThat($response->headers->has('laravel-responsecache'), isFalse(), 'Failed to assert that the response was a regular response');
}

function assertSameResponse(TestResponse $firstResponse, TestResponse $secondResponse)
{
    test()->assertThat($firstResponse->getContent() === $secondResponse->getContent(), isTrue(), 'Failed to assert that two response are the same');
}

function assertDifferentResponse(TestResponse $firstResponse, TestResponse $secondResponse)
{
    test()->assertThat($firstResponse->getContent() !== $secondResponse->getContent(), isTrue(), 'Failed to assert that two response are different');
}

/**
 * Create a new request with the given method.
 */
function createRequest(string $method): Request
{
    $request = new Request();

    $request->setMethod($method);

    return $request;
}

/**
 * Create a new response with the given statusCode.
 */
function createResponse(int $statusCode, string $contentType = 'text/html; charset=UTF-8'): Response
{
    $response = new Response();

    $response
        ->setStatusCode($statusCode)
        ->headers->set('Content-Type', $contentType);

    return $response;
}
