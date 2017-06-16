<?php

namespace Spatie\ResponseCache;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseSerializer
{
    const RESPONSE_TYPE_NORMAL = 1;
    const RESPONSE_TYPE_FILE = 2;

    public function serialize(Response $response): string
    {
        $type = self::RESPONSE_TYPE_NORMAL;

        if ($response instanceof BinaryFileResponse) {
            $content = $response->getFile()->getPathname();
            $type = self::RESPONSE_TYPE_FILE;
        } else {
            $content = $response->getContent();
        }

        $statusCode = $response->getStatusCode();
        $headers = $response->headers;

        return serialize(compact('content', 'statusCode', 'headers', 'type'));
    }

    public function unserialize(string $serializedResponse): Response
    {
        $responseProperties = unserialize($serializedResponse);

        $type = $responseProperties['type'] ?? self::RESPONSE_TYPE_NORMAL;

        if ($type === self::RESPONSE_TYPE_FILE) {
            $response = new BinaryFileResponse($responseProperties['content'], $responseProperties['statusCode']);
        } else {
            $response = new Response($responseProperties['content'], $responseProperties['statusCode']);
        }

        $response->headers = $responseProperties['headers'];

        return $response;
    }
}
