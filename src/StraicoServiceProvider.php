<?php

namespace r5dy1n\Straico;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use GuzzleHttp\Client;

class StraicoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/straico.php' => config_path('straico.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/straico.php', 'straico'
        );

        $this->app->singleton(StraicoService::class, function (Container $app) {
            $config = $app->make('config');
            $apiKey = $config->get('straico.api_key');
            $baseUrl = $config->get('straico.base_url');
            $timeout = $config->get('straico.timeout', 60); // Default timeout 60 seconds, matching service default

            // Validation is now handled within StraicoService constructor
            // if (empty($apiKey)) { ... }
            // if (empty($baseUrl)) { ... }

            // Pass config values directly to the service constructor
            return new StraicoService($apiKey, $baseUrl, $timeout);
        });

        $this->app->alias(StraicoService::class, 'straico');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [StraicoService::class, 'straico'];
    }
}