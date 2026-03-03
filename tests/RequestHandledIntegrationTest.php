<?php

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

it('includes modifications made by RequestHandled listeners in the cached response', function () {
    // This flag simulates whether something (e.g. a Livewire component) was rendered
    // during this request — mirroring Livewire's $hasRenderedAComponentThisRequest.
    $rendered = false;

    Route::get('/with-listener', function () use (&$rendered) {
        $rendered = true;

        return '<html><head></head><body>page content</body></html>';
    })->middleware(CacheResponse::class);

    // Simulate Livewire-like conditional asset injection: only injects when the
    // flag is set (i.e. a component rendered), then resets it. On a cache hit
    // the route never runs so the flag stays false and no injection happens.
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

    // First request (cache miss): the RequestHandled listener fires after handle()
    // returns and injects the script into the response.
    $firstResponse = $this->get('/with-listener');
    assertRegularResponse($firstResponse);
    expect($firstResponse->getContent())->toContain('<script src="livewire.js"></script>');

    // $rendered is now false — the listener will not inject on the next request.
    // The cached response must already contain the script from the first request.
    $secondResponse = $this->get('/with-listener');
    assertCachedResponse($secondResponse);
    expect($secondResponse->getContent())->toContain('<script src="livewire.js"></script>');

    assertSameResponse($firstResponse, $secondResponse);
});
