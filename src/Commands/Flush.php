<?php

namespace Spatie\ResponseCache\Commands;

use Illuminate\Console\Command;

class Flush extends Command
{
    protected $signature = 'responsecache:flush';

    protected $description = 'Flush the response cache (deprecated - use the clear method)';

    public function handle()
    {
        $this->call('responsecache:clear');
    }
}
