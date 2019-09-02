<?php

namespace Spatie\ResponseCache\Serializer;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExampleSerializer extends DefaultSerializer
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

        // If you return it with JsonResponse, save the content as JSON
        $content = $response instanceof JsonResponse
            ? $response->getData()
            : $response->getContent();

        $type = self::RESPONSE_TYPE_NORMAL;

        // Save class name
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

        // Restore as saved class
        return new $class($responseProperties['content'], $responseProperties['statusCode']);
    }
}
