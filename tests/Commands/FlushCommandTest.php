<?php

namespace Spatie\ResponseCache\Test\Commands;

use Illuminate\Support\Facades\Artisan;
use Spatie\ResponseCache\Test\TestCase;

class FlushCommandTest extends TestCase
{
    /** @test */
    public function it_points_to_the_updated_command()
    {
        $clearCommand = \Mockery::mock("\Spatie\ResponseCache\Commands\Clear[handle]");
        $clearCommand->shouldReceive('handle')->once();
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($clearCommand);

        Artisan::call('responsecache:flush');
    }
}
