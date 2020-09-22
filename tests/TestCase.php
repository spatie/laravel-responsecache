<?php

namespace Spatie\ResponseCache\Test;

use File;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as Orchestra;
use Route;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;
use Spatie\ResponseCache\ResponseCacheServiceProvider;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app['responsecache']->clear();

        $this->initializeDirectory($this->getTempDirectory());

        $this->setUpDatabase($this->app);

        $this->setUpRoutes($this->app);

        $this->setUpMiddleware();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ResponseCacheServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'ResponseCache' => ResponseCache::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);

        config()->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);

        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->rememberToken();
            $table->timestamps();
        });

        foreach (range(1, 10) as $index) {
            User::create(
                [
                    'name' => "user{$index}",
                    'email' => "user{$index}@spatie.be",
                    'password' => "password{$index}",
                ]
            );
        }
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpRoutes($app)
    {
        Route::middleware(CacheResponse::class)->group(function () {
            Route::any('/', function () {
                return 'home of '.(auth()->check() ? auth()->user()->id : 'anonymous');
            });

            Route::any('login/{id}', function ($id) {
                auth()->login(User::find($id));

                return redirect('/');
            });

            Route::any('logout', function () {
                auth()->logout();

                return redirect('/');
            });

            Route::any('/random/{id?}', function () {
                return Str::random();
            });

            Route::any('/csrf_token', ['middleware' => 'web', function () {
                return csrf_token();
            }]);

            Route::any('/redirect', function () {
                return redirect('/');
            });

            Route::any('/uncacheable', ['middleware' => 'doNotCacheResponse', function () {
                return 'uncacheable '.Str::random();
            }]);

            Route::any('/image', function () {
                return response()->file(__DIR__.'/User.php');
            });

            Route::any('/tagged/1', function () {
                return Str::random();
            })->middleware('cacheResponse:,foo');

            Route::any('/tagged/2', function () {
                return Str::random();
            })->middleware('cacheResponse:,foo,bar');
        });

        Route::any('/cache-for-given-lifetime', function () {
            return 'dummy response';
        })->middleware('cacheResponse:300');
    }

    public function getTempDirectory($suffix = '')
    {
        return __DIR__.DIRECTORY_SEPARATOR.'temp'.($suffix == '' ? '' : DIRECTORY_SEPARATOR.$suffix);
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    protected function assertCachedResponse(TestResponse $response)
    {
        self::assertThat($response->headers->has('laravel-responsecache'), self::isTrue(), 'Failed to assert that the response has been cached');
    }

    protected function assertRegularResponse(TestResponse $response)
    {
        self::assertThat($response->headers->has('laravel-responsecache'), self::isFalse(), 'Failed to assert that the response was a regular response');
    }

    protected function assertSameResponse(TestResponse $firstResponse, TestResponse $secondResponse)
    {
        self::assertThat($firstResponse->getContent() === $secondResponse->getContent(), self::isTrue(), 'Failed to assert that two response are the same');
    }

    protected function assertDifferentResponse(TestResponse $firstResponse, TestResponse $secondResponse)
    {
        self::assertThat($firstResponse->getContent() !== $secondResponse->getContent(), self::isTrue(), 'Failed to assert that two response are different');
    }

    protected function setUpMiddleware()
    {
        $this->app[Router::class]->aliasMiddleware('doNotCacheResponse', DoNotCacheResponse::class);
        $this->app[Router::class]->aliasMiddleware('cacheResponse', CacheResponse::class);
    }
}
