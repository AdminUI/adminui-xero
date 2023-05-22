<?php

namespace AdminUI\AdminUIXero;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Facades\Seeder;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUIXero\Models\XeroToken;
use Illuminate\Console\Scheduling\Schedule;
use AdminUI\AdminUIXero\Commands\CopyContacts;
use AdminUI\AdminUIXero\Commands\InstallAUIXero;
use AdminUI\AdminUIXero\Providers\XeroServiceProvider;
use AdminUI\AdminUIXero\Providers\EventServiceProvider;
use AdminUI\AdminUIXero\Database\Seeds\NavigationSeeder;
use AdminUI\AdminUIXero\Providers\ConfigServiceProvider;
use AdminUI\AdminUIXero\Database\Seeds\ConfigurationSeeder;

class Provider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ConfigServiceProvider::class);
        $this->app->register(XeroServiceProvider::class);

        $baseDir = dirname(__DIR__);

        $this->loadRoutesFrom(__DIR__ . '/routes/admin.php');

        $this->publishes([
            $baseDir . '/publish/config' => config_path(),
            $baseDir . '/publish/js' => public_path('vendor/adminui-xero')
        ], 'auixero');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallAUIXero::class]);
            $this->commands([CopyContacts::class]);
        }

        if (!$this->app->runningInConsole()) {

            $this->pushJavascript();
            $tenant = XeroToken::latest()->first();
            if ($tenant) {
                Xero::setTenantId($tenant->id);
            }
        }

        Seeder::add([NavigationSeeder::class, ConfigurationSeeder::class]);

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('xero:keep-alive')->everyMinute();
        });
    }

    public function pushJavascript()
    {
        $output = \Illuminate\Support\Facades\Vite::useHotFile(base_path('vendor/adminui/adminui-xero/publish/js/hot'))
            ->withEntryPoints(['resources/index.js'])
            ->useBuildDirectory('vendor/adminui-xero')
            ->toHtml();

        View::startPush('aui_packages', $output);
    }
}
