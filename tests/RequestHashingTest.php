<?php

use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\DefaultHasher;

class CountingHasher extends DefaultHasher
{
    public static int $calls = 0;

    public function getHashFor(Request $request): string
    {
        static::$calls++;

        return parent::getHashFor($request);
    }
}

beforeEach(function () {
    CountingHasher::$calls = 0;
    config()->set('responsecache.hasher', CountingHasher::class);
});

it('hashes the request once when serving a cached response', function () {
    $this->get('/random');

    CountingHasher::$calls = 0;

    assertCachedResponse($this->get('/random'));
    expect(CountingHasher::$calls)->toBe(1);
});

it('hashes the request once when serving a cached flexible response', function () {
    $this->get('/flexible/basic');

    CountingHasher::$calls = 0;

    $this->get('/flexible/basic');
    expect(CountingHasher::$calls)->toBe(1);
});
