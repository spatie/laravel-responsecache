<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Serializers\Serializer;
use Spatie\ResponseCache\Test\Serializers\TestSerializer;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    $this->textContent = '<html>This is a response</html>';
    $this->jsonContent = json_encode(['text' => 'This is a response']);

    $this->statusCode = 500;
});

it('can serialize and unserialize a response', function () {
    // Instantiate a default serializer
    $responseSerializer = app(Serializer::class);

    $testResponse = new Response(
        $this->textContent,
        $this->statusCode,
        ['testHeader' => 'testValue']
    );

    $serializedResponse = $responseSerializer->serialize($testResponse);

    assertTrue(is_string($serializedResponse));

    $unserializedResponse = $responseSerializer->unserialize($serializedResponse);

    assertInstanceOf(Response::class, $unserializedResponse);

    assertEquals($this->textContent, $unserializedResponse->getContent());

    assertEquals($this->statusCode, $unserializedResponse->getStatusCode());

    assertEquals('testValue', $unserializedResponse->headers->get('testHeader'));
});

it('can customize serialize and unserialize a response', function () {
    // Set config dynamically for test
    Config::set('responsecache.serializer', TestSerializer::class);

    // Instantiate a custom serializer according to config
    $responseSerializer = app(Serializer::class);

    $testResponse = new JsonResponse(
        $this->jsonContent,
        $this->statusCode,
        ['testHeader' => 'testValue']
    );

    $serializedResponse = $responseSerializer->serialize($testResponse);

    assertTrue(is_string($serializedResponse));

    $unserializedResponse = $responseSerializer->unserialize($serializedResponse);

    assertInstanceOf(JsonResponse::class, $unserializedResponse);

    assertEquals($this->jsonContent, $unserializedResponse->getData());

    assertEquals($this->statusCode, $unserializedResponse->getStatusCode());

    assertEquals('testValue', $unserializedResponse->headers->get('testHeader'));
});

it('throws an exception when something else than a response is unserialized', function () {
    app(Serializer::class)->unserialize('b:0;');
})->throws(CouldNotUnserialize::class);
