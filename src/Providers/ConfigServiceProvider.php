<?php

namespace AdminUI\AdminUIXero\Providers;

use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\Facades\App;
use AdminUI\AdminUI\Events\NewOrder;
use AdminUI\Xero\Models\XeroToken;
use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUI\Models\Configuration;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../publish/config/xero.php', 'xero');
    }
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        $enabled = Configuration::firstWhere('name', 'xero_enabled');
        $clientId = Configuration::firstWhere('name', 'xero_client_id');
        $clientSecret = Configuration::firstWhere('name', 'xero_client_secret');
        $accountId = Configuration::firstWhere('name', 'xero_account_id');

        $root = App::make('url')->to('/' . config('adminui.prefix'));

        config([
            'xero.enabled' => !empty($enabled) ? $enabled->value : false,
            'xero.clientId' => !empty($clientId) ? $clientId->value : null,
            'xero.clientSecret' => !empty($clientSecret) ? $clientSecret->value : null,
            'xero.accountId' => !empty($accountId) ? $accountId->value : null,
            'xero.redirectUri' => $root . '/xero/connect',
            'xero.landingUri' => $root . '/setup/xero'
        ]);
    }
}
