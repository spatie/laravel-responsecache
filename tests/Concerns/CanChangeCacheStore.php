<?php

namespace Spatie\ResponseCache\Test\Concerns;

trait CanChangeCacheStore
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Set the driver to array (tags don't work with the file driver)
        config()->set('responsecache.cache_store', 'array');
        config()->set('responsecache.cache_tag', 'tagging-test');
    }
}
