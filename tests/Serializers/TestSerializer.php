<?php

namespace Spatie\ResponseCache\Test\Serializers;

use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Enums\ResponseType;
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
            $type = ResponseType::File->value;

            return compact('statusCode', 'headers', 'content', 'type');
        }

        $content = $response instanceof JsonResponse
            ? $response->getData()
            : $response->getContent();

        $type = ResponseType::Normal->value;

        $class = get_class($response);

        return compact('statusCode', 'headers', 'content', 'type', 'class');
    }

    protected function buildResponse(array $responseProperties): Response
    {
        $type = $responseProperties['type'] ?? ResponseType::Normal->value;

        if ($type === ResponseType::File->value) {
            return new BinaryFileResponse(
                $responseProperties['content'],
                $responseProperties['statusCode']
            );
        }

        $class = $responseProperties['class'];

        return new $class($responseProperties['content'], $responseProperties['statusCode']);
    }
}
