<?php

namespace Spatie\ResponseCache\Serializers;

use Illuminate\Http\Response as IlluminateResponse;
use JsonException;
use Spatie\ResponseCache\Enums\ResponseType;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class RawBodySerializer implements Serializer
{
    public function serialize(Response $response): string
    {
        $isFile = $response instanceof BinaryFileResponse;

        $metadata = json_encode([
            'type' => ($isFile ? ResponseType::File : ResponseType::Normal)->value,
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $content = $isFile ? $response->getFile()->getPathname() : (string) $response->getContent();

        // json_encode never emits a raw newline, so the first one always ends the metadata.
        return $metadata."\n".$content;
    }

    public function unserialize(string $serializedResponse): Response
    {
        $separator = strpos($serializedResponse, "\n");

        if ($separator === false) {
            return (new JsonSerializer)->unserialize($serializedResponse);
        }

        try {
            $metadata = json_decode(substr($serializedResponse, 0, $separator), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        if (! is_array($metadata) || ! isset($metadata['type'], $metadata['status'], $metadata['headers'])) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        $content = substr($serializedResponse, $separator + 1);

        return match (ResponseType::tryFrom($metadata['type'])) {
            ResponseType::Normal => new IlluminateResponse($content, $metadata['status'], $metadata['headers']),
            ResponseType::File => $this->fileResponse($content, $metadata['status'], $metadata['headers']),
            null => throw CouldNotUnserialize::serializedResponse($serializedResponse),
        };
    }

    protected function fileResponse(string $path, int $status, array $headers): BinaryFileResponse
    {
        $response = new BinaryFileResponse($path, $status);

        // The constructor marks the response public, so stored headers must be applied afterwards to win.
        foreach ($headers as $name => $values) {
            $response->headers->set($name, $values);
        }

        return $response;
    }
}
