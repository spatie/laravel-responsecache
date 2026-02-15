<?php

namespace Spatie\ResponseCache\Serializers;

use Illuminate\Http\Response as IlluminateResponse;
use Spatie\ResponseCache\Enums\ResponseType;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class JsonSerializer implements Serializer
{
    public function serialize(Response $response): string
    {
        $type = match (true) {
            $response instanceof BinaryFileResponse => ResponseType::File,
            default => ResponseType::Normal,
        };

        $data = [
            'type' => $type->value,
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => $response instanceof BinaryFileResponse
                ? $response->getFile()->getPathname()
                : $response->getContent(),
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function unserialize(string $serializedResponse): Response
    {
        try {
            $data = json_decode($serializedResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        if (! is_array($data) || ! isset($data['type'], $data['status'], $data['headers'], $data['content'])) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        $type = ResponseType::from($data['type']);

        $response = match ($type) {
            ResponseType::File => new BinaryFileResponse(
                $data['content'],
                $data['status']
            ),
            ResponseType::Normal => new IlluminateResponse(
                $data['content'],
                $data['status']
            ),
        };

        foreach ($data['headers'] as $name => $values) {
            $response->headers->set($name, $values);
        }

        return $response;
    }
}
