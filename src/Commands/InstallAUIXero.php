<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUIXero\Database\Seeds\NavigationSeeder;

class InstallAUIXero extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:installxero';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Xero onto AdminUI';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Run the admin navgation again
        $seederNav = new NavigationSeeder();
        $seederNav->run();

        // Publish the config file
        Artisan::call('vendor:publish', [
            '--provider' => 'AdminUI\Xero\XeroServiceProvider',
            '--force'    => true
        ]);
        Artisan::call('vendor:publish', [
            '--tag' => 'auixero'
        ]);

        $env = '

XERO_CLIENT_ID=
XERO_CLIENT_SECRET=
XERO_REDIRECT_URL=https://domain.com/xero/connect
XERO_LANDING_URL=https://domain.com/xero
XERO_WEBHOOK_KEY=
        ';

        file_put_contents('.env', $env, FILE_APPEND);
        // Clear Cache
        Cache::flush();

        // send a message back
        $this->info('AdminUI Xero package has been added');
    }
}
