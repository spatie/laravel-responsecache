<?php

namespace Spatie\ResponseCache\Enums;

enum CacheStrategy
{
    case Standard;
    case Flexible;     
    case Disabled;
}
