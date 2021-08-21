<?php

namespace Spatie\ResponseCache\Test\Replacers;

use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;
use Spatie\ResponseCache\ResponseCacheConfig;
use Spatie\ResponseCache\Test\TestCase;

class CsrfTokenReplacerTest extends TestCase
{
    /** @test
     * @throws InvalidConfig
     */
    public function it_will_refresh_csrf_token_on_cached_response()
    {
        $config = $this->getConfig();
        $config['replacers'] = [
            CsrfTokenReplacer::class,
        ];
        $cacheConfig = new ResponseCacheConfig($config);
        app()->instance(ResponseCacheConfig::class, $cacheConfig);

        session()->regenerateToken();

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
    }
}
