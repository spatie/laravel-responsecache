<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Illuminate\Http\Request;
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
