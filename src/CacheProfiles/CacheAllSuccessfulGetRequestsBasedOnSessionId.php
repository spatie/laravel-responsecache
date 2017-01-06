<?php

namespace Spatie\ResponseCache\CacheProfiles;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheAllSuccessfulGetRequestsBasedOnSessionId extends CacheAllSuccessfulGetRequests
{
    /**
     * Set a string to add to differentiate this request from others.
     *
     * @return string
     */
    public function cacheNameSuffix(Request $request)
    {
        return $this->app->session->all();
    }



}
