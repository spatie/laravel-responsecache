<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Response;
use Spatie\ResponseCache\ResponseSerializer;

class ResponseSerializerTest extends TestCase
{
    /**
     * @var \Spatie\ResponseCache\ResponseSerializer
     */
    protected $responseSerializer;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $statusCode;

    public function setUp()
    {
        parent::setUp();

        $this->responseSerializer = new ResponseSerializer();

        $this->content = '<html>This is a reponse</html>';

        $this->statusCode = 500;
    }

    /**
     * @test
     */
    public function it_can_serialize_and_unserialize_a_reponse()
    {
        $testResponse = Response::create($this->content, $this->statusCode, ['testHeader' => 'testValue']);

        $serializedResponse = $this->responseSerializer->serialize($testResponse);

        $this->assertTrue(is_string($serializedResponse));

        $unserializedResponse = $this->responseSerializer->unserialize($serializedResponse);

        $this->assertInstanceOf(Response::class, $unserializedResponse);

        $this->assertEquals($this->content, $unserializedResponse->getContent());

        $this->assertEquals($this->statusCode, $unserializedResponse->getStatusCode());

        $this->assertEquals('testValue', $unserializedResponse->headers->get('testHeader'));
    }
}
