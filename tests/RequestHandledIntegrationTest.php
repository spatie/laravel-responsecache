<?php

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

it('includes modifications made by RequestHandled listeners in the cached response', function () {
    $rendered = false;

    Route::get('/with-listener', function () use (&$rendered) {
        $rendered = true;

        return '<html><head></head><body>page content</body></html>';
    })->middleware(CacheResponse::class);

    app('events')->listen(RequestHandled::class, function ($handled) use (&$rendered) {
        if (! $rendered) {
            return;
        }

        $rendered = false;

        $content = $handled->response->getContent();
        $handled->response->setContent(
            str_replace('</head>', '<script src="livewire.js"></script></head>', $content)
        );
    });

    $firstResponse = $this->get('/with-listener');
    assertRegularResponse($firstResponse);
    expect($firstResponse->getContent())->toContain('<script src="livewire.js"></script>');

    $secondResponse = $this->get('/with-listener');
    assertCachedResponse($secondResponse);
    expect($secondResponse->getContent())->toContain('<script src="livewire.js"></script>');

    assertSameResponse($firstResponse, $secondResponse);
});
