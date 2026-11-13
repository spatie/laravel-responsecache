<?php

use Illuminate\Http\Response;
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

it('replaces every csrf token with a placeholder and back', function () {
    session()->regenerateToken();
    $token = csrf_token();
    $html = "{$token}<meta content=\"{$token}\"><input value=\"{$token}\">{$token}";
    $response = new Response($html);
    $replacer = new CsrfTokenReplacer;

    $replacer->prepareResponseToCache($response);

    expect($response->getContent())->not->toContain($token)
        ->and(substr_count($response->getContent(), '<laravel-responsecache-csrf-token-here>'))->toBe(4);

    session()->regenerateToken();
    $newToken = csrf_token();

    $replacer->replaceInCachedResponse($response);

    expect($response->getContent())->toBe("{$newToken}<meta content=\"{$newToken}\"><input value=\"{$newToken}\">{$newToken}");
});

it('leaves responses without a csrf token or placeholder untouched', function (string $content) {
    $response = new Response($content);
    $replacer = new CsrfTokenReplacer;

    $replacer->prepareResponseToCache($response);
    $replacer->replaceInCachedResponse($response);

    expect($response->getContent())->toBe($content);
})->with(['<p>No token here</p>', '', '0']);
