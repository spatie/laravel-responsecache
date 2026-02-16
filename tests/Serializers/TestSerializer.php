<?php

namespace Spatie\ResponseCache\Test\Serializers;

use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Enums\ResponseType;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Serializers\Serializer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class TestSerializer implements Serializer
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
            'content' => $type === ResponseType::File
                ? $response->getFile()->getPathname()
                : ($response instanceof JsonResponse ? $response->getData() : $response->getContent()),
            'class' => get_class($response),
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function unserialize(string $serializedResponse): Response
    {
        try {
            $data = json_decode($serializedResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        if (! is_array($data) || ! isset($data['type'], $data['status'], $data['headers'], $data['content'])) {
            throw CouldNotUnserialize::serializedResponse($serializedResponse);
        }

        $type = ResponseType::from($data['type']);

        if ($type === ResponseType::File) {
            $response = new BinaryFileResponse($data['content'], $data['status']);
        } else {
            $class = $data['class'] ?? Response::class;
            $response = new $class($data['content'], $data['status']);
        }

        foreach ($data['headers'] as $name => $values) {
            $response->headers->set($name, $values);
        }

        return $response;
    }
}
