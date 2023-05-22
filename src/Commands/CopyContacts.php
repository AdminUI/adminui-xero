<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUIXero\Controllers\XeroController;

class CopyContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:xerocontacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer Xero Contacts onto AdminUI';

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
        return XeroController::allocateAccounts();
    }
}
