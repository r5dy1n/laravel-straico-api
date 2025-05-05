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

            if (empty($apiKey)) {
                throw new \InvalidArgumentException('Straico API key is missing. Please set it in your .env file or config/straico.php.');
            }
             if (empty($baseUrl)) {
                throw new \InvalidArgumentException('Straico Base URL is missing. Please set it in your .env file or config/straico.php.');
            }

            $client = new Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'timeout' => $config->get('straico.timeout', 30), // Default timeout 30 seconds
            ]);

            return new StraicoService($client);
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