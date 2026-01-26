<?php

namespace Spatie\ResponseCache\Enums;

enum CacheStrategy
{
    case Standard;      // Traditional TTL-based caching
    case Flexible;      // Stale-while-revalidate with async refresh
    case Disabled;      // Explicitly disabled for route
}
