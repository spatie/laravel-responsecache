<?php

namespace Spatie\ResponseCache\Test;

use Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Serializers\Serializer;
use Spatie\ResponseCache\Test\Serializers\TestSerializer;

class ResponseSerializerTest extends TestCase
{
    /** @var string */
    protected $textContent;

    /** @var string */
    protected $jsonContent;

    /** @var string */
    protected $statusCode;

    public function setUp(): void
    {
        parent::setUp();

        $this->textContent = '<html>This is a response</html>';
        $this->jsonContent = json_encode(['text' => 'This is a response']);

        $this->statusCode = 500;
    }

    /** @test */
    public function it_can_serialize_and_unserialize_a_response()
    {
        // Instantiate a default serializer
        $responseSerializer = app(Serializer::class);

        $testResponse = Response::create(
            $this->textContent, $this->statusCode, ['testHeader' => 'testValue']);

        $serializedResponse = $responseSerializer->serialize($testResponse);

        $this->assertTrue(is_string($serializedResponse));

        $unserializedResponse = $responseSerializer->unserialize($serializedResponse);

        $this->assertInstanceOf(Response::class, $unserializedResponse);

        $this->assertEquals($this->textContent, $unserializedResponse->getContent());

        $this->assertEquals($this->statusCode, $unserializedResponse->getStatusCode());

        $this->assertEquals('testValue', $unserializedResponse->headers->get('testHeader'));
    }

    /** @test */
    public function it_can_customized_serialize_and_unserialize_a_response()
    {
        // Set config dynamically for test
        Config::set('responsecache.serializer', TestSerializer::class);

        // Instantiate a custom serializer according to config
        $responseSerializer = app(Serializer::class);

        $testResponse = JsonResponse::create(
            $this->jsonContent, $this->statusCode, ['testHeader' => 'testValue']);

        $serializedResponse = $responseSerializer->serialize($testResponse);

        $this->assertTrue(is_string($serializedResponse));

        $unserializedResponse = $responseSerializer->unserialize($serializedResponse);

        $this->assertInstanceOf(JsonResponse::class, $unserializedResponse);

        $this->assertEquals($this->jsonContent, $unserializedResponse->getData());

        $this->assertEquals($this->statusCode, $unserializedResponse->getStatusCode());

        $this->assertEquals('testValue', $unserializedResponse->headers->get('testHeader'));
    }

    /** @test */
    public function it_throws_an_exception_when_something_else_than_a_response_is_unserialized()
    {
        $this->expectException(CouldNotUnserialize::class);

        app(Serializer::class)->unserialize('b:0;');
    }
}
