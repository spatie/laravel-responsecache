<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;

class CacheAllSuccessfulGetRequestsBasedOnSessionId extends CacheAllSuccessfulGetRequests
{
    /**
     * Set a string to add to differentiate this request from others.
     *
     * @return string
     */
    public function cacheNameSuffix(Request $request)
    {
        if(empty($this->app->session->all())) {
            return 'empty';
        } else {
            return $this->app->session->getId();
        }
    }



}
