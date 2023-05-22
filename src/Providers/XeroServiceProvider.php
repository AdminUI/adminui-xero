<?php

namespace AdminUI\AdminUIXero\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUIXero\XeroAuthenticated;
use AdminUI\AdminUIXero\Xero;
use AdminUI\AdminUIXero\Commands\XeroKeepAliveCommand;
use AdminUI\AdminUIXero\Commands\XeroShowAllCommand;


class XeroServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerCommands();
        $this->registerMiddleware($router);
    }

    public function registerCommands()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            XeroKeepAliveCommand::class,
            XeroShowAllCommand::class,
            XeroPushOrders::class
        ]);
    }

    public function registerMiddleware($router)
    {
        //add middleware
        $router->aliasMiddleware('XeroAuthenticated', XeroAuthenticated::class);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Register the service the package provides.
        $this->app->singleton('xero', function ($app) {
            return new Xero;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['xero'];
    }
}
