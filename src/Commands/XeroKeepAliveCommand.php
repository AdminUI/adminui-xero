<?php

namespace AdminUI\AdminUIXero\Commands;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUIXero\Models\XeroToken;
use Illuminate\Console\Command;

class XeroKeepAliveCommand extends Command
{
    protected $signature   = 'adminui:xero-keep-alive';
    protected $description = 'Run this command to refresh token if its due to expire. schedule this to run daily to avoid token expiring when using CLI commands';

    public function handle()
    {
        // Fetch all tenants for when multiple tenants are in use.
        $tenants = XeroToken::all();

        foreach ($tenants as $tenant) {

            // Set the tenant ID
            Xero::setTenantId($tenant->id);

            if (Xero::isConnected()) {
                Xero::getAccessToken($redirectWhenNotConnected = false);
            }
        }
    }
}
