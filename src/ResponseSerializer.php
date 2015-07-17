<?php

namespace Spatie\ResponseCache;

use Illuminate\Http\Response;

class ResponseSerializer
{
    public function serialize(Response $response)
    {
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->headers;

        return serialize(compact('content', 'statusCode', 'headers'));
    }

    public function unserialize($serializedResponse)
    {
        $responseProperties = unserialize($serializedResponse);

        $response = new Response($responseProperties['content'], $responseProperties['statusCode']);

        $response->headers = $responseProperties['headers'];

        return $response;
    }
}
