<?php

namespace Spatie\ResponseCache\Test\Serializers;

use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Serializers\DefaultSerializer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class TestSerializer extends DefaultSerializer
{
    protected function getResponseData(Response $response): array
    {
        $statusCode = $response->getStatusCode();
        $headers = $response->headers;

        if ($response instanceof BinaryFileResponse) {
            $content = $response->getFile()->getPathname();
            $type = self::RESPONSE_TYPE_FILE;

            return compact('statusCode', 'headers', 'content', 'type');
        }

        $content = $response instanceof JsonResponse
            ? $response->getData()
            : $response->getContent();

        $type = self::RESPONSE_TYPE_NORMAL;

        $class = get_class($response);

        return compact('statusCode', 'headers', 'content', 'type', 'class');
    }

    protected function buildResponse(array $responseProperties): Response
    {
        $type = $responseProperties['type'] ?? self::RESPONSE_TYPE_NORMAL;

        if ($type === self::RESPONSE_TYPE_FILE) {
            return new BinaryFileResponse(
                $responseProperties['content'],
                $responseProperties['statusCode']
            );
        }

        $class = $responseProperties['class'];

        return new $class($responseProperties['content'], $responseProperties['statusCode']);
    }
}
