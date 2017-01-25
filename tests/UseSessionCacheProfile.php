<?php

namespace Spatie\ResponseCache\Test;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

class UseSessionCacheProfile extends CacheAllSuccessfulGetRequests
{
    /**
     * Set a string to add to differentiate this request from others.
     *
     * @param Request $request
     * @return string
     */
    public function cacheNameSuffix(Request $request)
    {
        if (empty($this->app->session->getId())) {
            return false;
        };

        $this->app->session->getId();
    }


}
