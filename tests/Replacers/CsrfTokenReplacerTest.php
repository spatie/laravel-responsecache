<?php

namespace Spatie\ResponseCache\Test\Replacers;

use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;

test('it will refresh csrf token on cached response', function () {
    session()->regenerateToken();

    config()->set('responsecache.replacers', [
        CsrfTokenReplacer::class,
    ]);

    $firstToken = csrf_token();
    $firstResponse = $this->get('/csrf_token');
    $firstResponse->assertSee($firstToken);

    session()->regenerateToken();

    $secondToken = csrf_token();
    $secondResponse = $this->get('/csrf_token');

    $this->assertRegularResponse($firstResponse);
    $this->assertCachedResponse($secondResponse);

    $secondResponse->assertDontSee($firstToken);
    $secondResponse->assertSee($secondToken);

    $this->assertDifferentResponse($firstResponse, $secondResponse);
});
