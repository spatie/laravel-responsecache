<?php

namespace Spatie\ResponseCache\Exceptions;

use Exception;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Serializers\Serializer;

class InvalidConfig extends Exception
{
    public static function invalidCacheProfile(string $cache_profile_class): self
    {
        $cache_profile_interface = CacheProfile::class;

        return new static("`$cache_profile_class` is not a valid cache profile class. A valid cache profile is a class that implements `$cache_profile_interface`.");
    }

    public static function invalidRequestHasher(string $request_hasher_class): self
    {
        $request_hasher_interface = RequestHasher::class;

        return new static("`$request_hasher_class` is not a valid request hasher class. A valid request hasher is a class that implements `$request_hasher_interface`.");
    }

    public static function invalidSerializer(string $serializer_class): self
    {
        $serializer_interface = Serializer::class;

        return new static("`$serializer_class` is not a valid serializer class. A valid serializer is a class that implements `$serializer_interface`.");
    }

    public static function couldNotFindConfig(string $config_name): self
    {
        return new static("`$config_name` is not a valid serializer class. A valid serializer is a class that implements");
    }
}
