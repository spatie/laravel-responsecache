<?php

namespace Spatie\ResponseCache;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResponseSerializer
{
    const RESPONSE_TYPE_NORMAL = 1;
    const RESPONSE_TYPE_FILE = 2;

    public function serialize(Response $response): string
    {
        return serialize($this->getResponseData($response));
    }

    public function unserialize(string $serializedResponse): Response
    {
        $responseProperties = unserialize($serializedResponse);

        $response = $this->buildResponse($responseProperties);

        $response->headers = $responseProperties['headers'];

        return $response;
    }

    private function getResponseData(Response $response): array
    {
        $type = self::RESPONSE_TYPE_NORMAL;
        $statusCode = $response->getStatusCode();
        $headers = $response->headers;

        if ($response instanceof BinaryFileResponse) {
            $content = $response->getFile()->getPathname();
            $type = self::RESPONSE_TYPE_FILE;

            return compact('content', 'statusCode', 'headers', 'type');
        }

        $content = $response->getContent();

        return compact('content', 'statusCode', 'headers', 'type');
    }

    private function buildResponse(array $responseProperties): Response
    {
        $type = $responseProperties['type'] ?? self::RESPONSE_TYPE_NORMAL;

        if ($type === self::RESPONSE_TYPE_FILE) {
            return new BinaryFileResponse($responseProperties['content'], $responseProperties['statusCode']);
        }

        return new Response($responseProperties['content'], $responseProperties['statusCode']);
    }
}
