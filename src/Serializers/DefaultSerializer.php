<?php

namespace Spatie\ResponseCache\Serializers;

use Illuminate\Http\Response as IlluminateResponse;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultSerializer implements Serializer
{
    public const RESPONSE_TYPE_NORMAL = 'normal';
    public const RESPONSE_TYPE_FILE = 'file';

    public function serialize(Response $response): string
    {
        return serialize($this->getResponseData($response));
    }

    public function unserialize(string $serializedResponse): Response
    {
        $responseProperties = unserialize($serializedResponse);

        if (! $this->containsValidResponseProperties($responseProperties)) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        $response = $this->buildResponse($responseProperties);

        $response->headers = $responseProperties['headers'];

        return $response;
    }

    protected function getResponseData(Response $response): array
    {
        $statusCode = $response->getStatusCode();
        $headers = $response->headers;

        if ($response instanceof BinaryFileResponse) {
            $content = $response->getFile()->getPathname();
            $type = static::RESPONSE_TYPE_FILE;

            return compact('statusCode', 'headers', 'content', 'type');
        }

        $content = $response->getContent();
        $type = static::RESPONSE_TYPE_NORMAL;

        return compact('statusCode', 'headers', 'content', 'type');
    }

    protected function containsValidResponseProperties($properties): bool
    {
        if (! is_array($properties)) {
            return false;
        }

        if (! isset($properties['content'], $properties['statusCode'])) {
            return false;
        }

        return true;
    }

    protected function buildResponse(array $responseProperties): Response
    {
        $type = $responseProperties['type'] ?? static::RESPONSE_TYPE_NORMAL;

        if ($type === static::RESPONSE_TYPE_FILE) {
            return new BinaryFileResponse(
                $responseProperties['content'],
                $responseProperties['statusCode']
            );
        }

        return new IlluminateResponse($responseProperties['content'], $responseProperties['statusCode']);
    }
}
