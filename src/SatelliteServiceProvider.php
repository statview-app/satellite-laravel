<?php

namespace Statview\Satellite;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Statview\Satellite\Console\Commands\TestWidgets;

class SatelliteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/statview.php', 'statview',
        );

        $this->registerFeatures();

        $this->registerDsn();

        $this->registerHttpMacro();
    }

    public function boot()
    {
        $this->bootRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestWidgets::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/statview.php' => config_path('statview.php'),
            ], 'statview-config');
        }
    }

    private function registerFeatures(): void
    {

    }

    private function registerDsn(): void
    {
        if (!filled(config('statview.dsn'))) {
            return;
        }

        $parseUrl = parse_url(config('statview.dsn'));

        $items = explode('/', $parseUrl['path']);

        Config::set('statview.endpoint', $parseUrl['scheme'] . '://' . $parseUrl['host']);
        Config::set('statview.project_id', $items[1]);
        Config::set('statview.api_key', $items[2]);
    }

    private function registerHttpMacro(): void
    {
        Http::macro('statviewClient', function () {
            $http = Http::baseUrl(config('statview.endpoint') . '/api/')
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('statview.api_key'),
                    'X-Statview-Client' => 'laravel',
                    'User-Agent' => 'Statview-Satellite-Laravel',
                ]);

            if (!config('statview.verify_ssl', true)) {
                $http->withoutVerifying();
            }

            return $http;
        });
    }

    private function bootRoutes(): void
    {
        Route::group([
            'domain' => config('statview.domain'),
            'prefix' => config('statview.path'),
            'middleware' => config('statview.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }
}
