<?php

namespace Spatie\ResponseCache\Serializer;

use Symfony\Component\HttpFoundation\Response;

interface Serializable
{
    public function serialize(Response $response): string;

    public function unserialize(string $serializedResponse): Response;
}
