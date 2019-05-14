<?php

namespace Spatie\ResponseCache\Test\Replacers;

use Spatie\ResponseCache\Test\TestCase;
use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;

class CsrfTokenReplacerTest extends TestCase
{
    /** @test */
    public function it_will_refresh_csrf_token_on_cached_response()
    {
        config()->set('responsecache.replacers', [
            CsrfTokenReplacer::class,
        ]);

        $firstResponse = $this->call('get', '/csrf_token');
        session()->regenerateToken();
        $secondResponse = $this->call('get', '/csrf_token');

        $this->assertRegularResponse($firstResponse);
        $this->assertCachedResponse($secondResponse);

        $this->assertDifferentResponse($firstResponse, $secondResponse);
    }
}
