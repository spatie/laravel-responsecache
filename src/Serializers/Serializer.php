<?php

namespace Spatie\ResponseCache\Serializers;

use Symfony\Component\HttpFoundation\Response;

interface Serializer
{
    public function serialize(Response $response): string;

    public function unserialize(string $serializedResponse): Response;
}
