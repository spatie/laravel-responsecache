<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\ResponseCacheConfig;
use Spatie\ResponseCache\ResponseCacheConfigSelector;

class ResponseCacheConfigSelectorTest extends TestCase
{
    /**
     * @throws InvalidConfig
     */
    public function setUp(): void
    {
        parent::setUp();

        $config = $this->getConfig();
        $config['cache_store'] = 'array';
        app()->instance(ResponseCacheConfig::class, new ResponseCacheConfig($config));

        // turn off exception handling
        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_should_return_valid_config_name()
    {
        // Arrange
        Route::any('/test', function () {
            return ResponseCacheConfigSelector::getConfig();
        })->middleware('cacheResponse:config-1');

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('config-1', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_inner_config_name_on_nested_routes()
    {
        // Arrange
        Route::middleware(['cacheResponse:config-1'])->group(function () {
            Route::any('/test', function () {
                return ResponseCacheConfigSelector::getConfig();
            })->middleware('cacheResponse:config-2');
        });

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('config-2', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_default_when_no_middleware()
    {
        // Arrange
        Route::any('/test', function () {
            return ResponseCacheConfigSelector::getConfig();
        });

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('default', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_default_config_when_no_param()
    {
        // Arrange
        Route::any('/test', function () {
            return ResponseCacheConfigSelector::getConfig();
        })->middleware('cacheResponse');

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('default', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_config_name_without_extra_params()
    {
        // Arrange
        Route::any('/test', function () {
            return ResponseCacheConfigSelector::getConfig();
        })->middleware('cacheResponse:config-1,300,foo,bar');

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('config-1', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_config_when_using_fqn()
    {
        // Arrange
        Route::any('/test', function () {
            return ResponseCacheConfigSelector::getConfig();
        })->middleware(CacheResponse::class.':config-1,300,foo,bar');

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('config-1', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_default_when_using_fqn_solely()
    {
        // Arrange
        Route::any('/test', function () {
            return ResponseCacheConfigSelector::getConfig();
        })->middleware(CacheResponse::class);

        // Act
        $response = $this->get('/test');

        // Assert
        $this->assertEquals('default', $response->getContent());
    }
}
