<?php

namespace Spatie\ResponseCache\Test\TestClasses;

use Attribute;
use Spatie\ResponseCache\Attributes\Cache;
use stdClass;

#[Attribute(Attribute::TARGET_METHOD)]
class ContextualCache extends Cache
{
    public stdClass $state;

    public function __construct()
    {
        parent::__construct(lifetime: config('responsecache.test_lifetime'));

        $this->state = new stdClass;
        $this->state->value = 'original';
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
class UncloneableCache extends Cache
{
    private function __clone() {}
}

class ContextualCacheController
{
    #[ContextualCache]
    public function __invoke(): string
    {
        return 'contextual';
    }

    #[UncloneableCache(lifetime: 37)]
    public function uncloneable(): string
    {
        return 'uncloneable';
    }
}
