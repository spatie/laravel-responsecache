<?php

namespace Spatie\ResponseCache\Test;

use Spatie\ResponseCache\Exceptions\InvalidConfig;
use Spatie\ResponseCache\ResponseCacheConfig;

class ResponseCacheConfigTest extends TestCase
{
    /**
     * @test
     * @throws InvalidConfig
     */
    public function it_can_handle_a_valid_configuration()
    {
        // Arrange
        $configArray = $this->getConfig();

        // Act
        $config = new ResponseCacheConfig($configArray);

        // Assert
        $this->assertEquals($configArray['name'], $config->name);
        $this->assertEquals($configArray['enabled'], $config->enabled);
        $this->assertEquals($configArray['cache_tag'], $config->cache_tag);
        $this->assertEquals($configArray['cache_store'], $config->cache_store);
        $this->assertEquals($configArray['cache_time_header_name'], $config->cache_time_header_name);
        $this->assertEquals($configArray['add_cache_time_header'], $config->add_cache_time_header);
        $this->assertEquals($configArray['cache_lifetime_in_seconds'], $config->cache_lifetime_in_seconds);

        $this->assertInstanceOf($configArray['hasher'], $config->hasher);
        $this->assertInstanceOf($configArray['serializer'], $config->serializer);
        $this->assertInstanceOf($configArray['cache_profile'], $config->cache_profile);
    }

    /** @test */
    public function it_validates_the_cache_profile()
    {
        $config = $this->getConfig();
        $config['cache_profile'] = 'invalid-cache-profile';

        $this->expectException(InvalidConfig::class);

        new ResponseCacheConfig($config);
    }

    /** @test */
    public function it_validates_the_serializer()
    {
        $config = $this->getConfig();
        $config['serializer'] = 'invalid-serializer';

        $this->expectException(InvalidConfig::class);

        new ResponseCacheConfig($config);
    }

    /** @test */
    public function it_validates_the_request_hasher()
    {
        $config = $this->getConfig();
        $config['serializer'] = 'invalid-request-hasher';

        $this->expectException(InvalidConfig::class);

        new ResponseCacheConfig($config);
    }
}
