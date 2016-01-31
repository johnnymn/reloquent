<?php

namespace Reloquent;

use Illuminate\Support\ServiceProvider;

class ReloquentServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('reloquent.php')
        ], 'config');

        $this->commands('Reloquent\Commands\GenerateModel');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Reloquent\Contracts\ReloquentClientContract', function ($app) {
            return new ReloquentClient($app['config']['reloquent.connection']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['reloquent-client'];
    }
}
