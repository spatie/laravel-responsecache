<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\ResponseCacheConfig;
use Spatie\ResponseCache\Serializers\Serializer;
use Spatie\ResponseCache\Test\TestClasses\TestSerializer;

class ResponseSerializerTest extends TestCase
{
    protected string $textContent;

    protected string $jsonContent;

    protected int $statusCode;

    protected ResponseCacheConfig $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->textContent = '<html lang="en">This is a response</html>';
        $this->jsonContent = json_encode(['text' => 'This is a response']);

        $this->statusCode = 500;


    }

    /** @test
     * @throws InvalidConfig
     */
    public function it_can_serialize_and_unserialize_a_response()
    {
        // Instantiate a default config
        $cacheConfig = new ResponseCacheConfig($this->getConfig());
        app()->instance(ResponseCacheConfig::class, $cacheConfig);

        // Instantiate a default serializer
        $responseSerializer = app(Serializer::class);

        $testResponse = new Response(
            $this->textContent,
            $this->statusCode,
            ['testHeader' => 'testValue']
        );

        $serializedResponse = $responseSerializer->serialize($testResponse);

        $this->assertTrue(is_string($serializedResponse));

        $unserializedResponse = $responseSerializer->unserialize($serializedResponse);

        $this->assertInstanceOf(Response::class, $unserializedResponse);

        $this->assertEquals($this->textContent, $unserializedResponse->getContent());

        $this->assertEquals($this->statusCode, $unserializedResponse->getStatusCode());

        $this->assertEquals('testValue', $unserializedResponse->headers->get('testHeader'));
    }

    /** @test
     * @throws InvalidConfig
     */
    public function it_can_use_custom_response_serializer()
    {
        // setup config
        $config = $this->getConfig();
        $config['serializer'] = TestSerializer::class;
        $cacheConfig = new ResponseCacheConfig($config);
        app()->instance(ResponseCacheConfig::class, $cacheConfig);

        // Instantiate a custom serializer according to config
        $responseSerializer = app(Serializer::class);

        $testResponse = new JsonResponse(
            $this->jsonContent,
            $this->statusCode,
            ['testHeader' => 'testValue']
        );

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
