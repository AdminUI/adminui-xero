<?php

namespace AdminUI\AdminUIXero\Listeners;

use AdminUI\AdminUI\Events\NewOrder;
use Facades\AdminUI\AdminUIXero\Controllers\XeroController;

class SendOrderToXero
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // ...
    }

    /**
     * Handle the event.
     */
    public function handle(NewOrder $event): void
    {
        $xeroEnabled = auiSetting('xero_enabled', false);

        if (!$xeroEnabled) {
            return;
        }

        XeroController::pushOrderToXero($event->order);
    }
}
