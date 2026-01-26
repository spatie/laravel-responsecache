<?php

namespace Spatie\ResponseCache\Serializers;

use Illuminate\Http\Response as IlluminateResponse;
use Spatie\ResponseCache\Enums\ResponseType;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated Use JsonSerializer instead. DefaultSerializer uses PHP serialize()
 *             which has security implications. This class will be removed in v9.0.
 */
class DefaultSerializer implements Serializer
{
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
            $type = ResponseType::File->value;

            return compact('statusCode', 'headers', 'content', 'type');
        }

        $content = $response->getContent();
        $type = ResponseType::Normal->value;

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
        $type = ResponseType::from($responseProperties['type'] ?? ResponseType::Normal->value);

        return match ($type) {
            ResponseType::File => new BinaryFileResponse(
                $responseProperties['content'],
                $responseProperties['statusCode']
            ),
            ResponseType::Normal => new IlluminateResponse(
                $responseProperties['content'],
                $responseProperties['statusCode']
            ),
        };
    }
}
