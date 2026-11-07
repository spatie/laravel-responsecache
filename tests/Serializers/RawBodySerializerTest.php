<?php

use Illuminate\Http\Response;
use Spatie\ResponseCache\Exceptions\CouldNotUnserialize;
use Spatie\ResponseCache\Serializers\JsonSerializer;
use Spatie\ResponseCache\Serializers\RawBodySerializer;
use Spatie\ResponseCache\Serializers\Serializer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

beforeEach(function () {
    $this->serializer = new RawBodySerializer;
});

it('is the default serializer', function () {
    expect(app(Serializer::class))->toBeInstanceOf(RawBodySerializer::class);
});

it('round trips a response', function (string $content) {
    $response = new Response($content, 201, ['X-Custom' => 'a/b é']);

    $unserialized = $this->serializer->unserialize($this->serializer->serialize($response));

    expect($unserialized)->toBeInstanceOf(Response::class)
        ->and($unserialized->getContent())->toBe($content)
        ->and($unserialized->getStatusCode())->toBe(201)
        ->and($unserialized->headers->get('X-Custom'))->toBe('a/b é');
})->with([
    'html' => '<a href="/path">Café “quoted”</a>',
    'json starting with a brace' => '{"title":"A \"quoted\" title","url":"/posts/1"}',
    'multiple lines' => "first line\nsecond line\r\n\nlast line\n",
    'non utf-8 bytes' => "caf\xE9 \x00 \xFF",
    'empty' => '',
]);

it('stores the body without escaping it', function () {
    $content = "<a href=\"/path\">Café</a>\n";

    $serialized = $this->serializer->serialize(new Response($content));

    expect($serialized)->toEndWith("\n".$content);
});

it('round trips a file response', function () {
    $response = new BinaryFileResponse(__FILE__, 200);

    $unserialized = $this->serializer->unserialize($this->serializer->serialize($response));

    expect($unserialized)->toBeInstanceOf(BinaryFileResponse::class)
        ->and($unserialized->getFile()->getPathname())->toBe(__FILE__);
});

it('reads entries written by the json serializer', function () {
    $response = new Response("<p>Café</p>\n<p>/path</p>", 200, ['X-Custom' => 'value']);

    $unserialized = $this->serializer->unserialize((new JsonSerializer)->serialize($response));

    expect($unserialized->getContent())->toBe("<p>Café</p>\n<p>/path</p>")
        ->and($unserialized->headers->get('X-Custom'))->toBe('value');
});

it('throws when the stored entry is not a serialized response', function (string $entry) {
    $this->serializer->unserialize($entry);
})->throws(CouldNotUnserialize::class)->with([
    'invalid metadata' => "not json\nbody",
    'missing keys' => "{\"status\":200}\nbody",
    'unknown type' => "{\"type\":\"other\",\"status\":200,\"headers\":{}}\nbody",
    'invalid legacy json' => 'b:0;',
]);
