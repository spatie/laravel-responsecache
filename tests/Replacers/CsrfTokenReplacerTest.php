<?php

use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;

it('will refresh csrf token on cached response', function () {
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

    assertRegularResponse($firstResponse);
    assertCachedResponse($secondResponse);

    $secondResponse->assertDontSee($firstToken);
    $secondResponse->assertSee($secondToken);

    assertDifferentResponse($firstResponse, $secondResponse);
});
