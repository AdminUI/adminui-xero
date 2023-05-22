<?php

namespace AdminUI\AdminUIXero\Database\Seeds;

use Illuminate\Database\Seeder;
use AdminUI\AdminUI\Models\Navigation;
use AdminUI\AdminUI\Models\Configuration;

class ConfigurationSeeder extends Seeder
{
    public function run()
    {

        Configuration::firstOrCreate([
            'name' => 'xero_enabled',
        ], [
            'label' => 'Enabled',
            'value' => false,
            'value_cast' => 'boolean',
            'section' => 'xero',
            'type' => 'switch',
            'is_private' => true,
            'is_active' => true
        ]);

        Configuration::firstOrCreate([
            'name' => 'xero_client_id',
        ], [
            'label' => 'Client ID',
            'value' => "",
            'section' => 'xero',
            'type' => 'text',
            'is_private' => true,
            'is_active' => true
        ]);

        Configuration::firstOrCreate([
            'name' => 'xero_client_secret',
        ], [
            'label' => 'Client Secret',
            'value' => "",
            'section' => 'xero',
            'type' => 'password',
            'is_private' => true,
            'is_active' => true
        ]);

        Configuration::firstOrCreate([
            'name' => 'xero_account_id',
        ], [
            'label' => 'Bank Account ID',
            'value' => "",
            'section' => 'xero',
            'type' => 'text',
            'is_private' => true,
            'is_active' => true
        ]);

        /*
        XERO_CLIENT_ID
XERO_CLIENT_SECRET
XERO_REDIRECT_URL
XERO_LANDING_URL
XERO_WEBHOOK_KEY=
*/
    }
}
