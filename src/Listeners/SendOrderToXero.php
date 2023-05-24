<?php

namespace AdminUI\AdminUIXero\Listeners;

use AdminUI\AdminUI\Events\NewOrder;
use AdminUI\AdminUIXero\Services\XeroContactService;
use AdminUI\AdminUIXero\Services\XeroInvoiceService;
use AdminUI\AdminUIXero\Services\XeroPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderToXero implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public XeroContactService $xeroContactService,
        public XeroInvoiceService $xeroInvoiceService,
        public XeroPaymentService $xeroPaymentService
    ) {
        // ...
    }

    /**
     * Handle the event to push an order to xero
     */
    public function handle(NewOrder $event): void
    {
        // This will push the order to xero
        // Check that Xero Push is enabled in Settings
        $xeroEnabled = auiSetting('xero_enabled', false);

        if (!$xeroEnabled) {
            return;
        }

        // Create or get new contact info
        if (!$event->order->account) {
            return;
        }

        // Get the account from xero, or create a new one
        $contact = $this->xeroContactService->getContact($event->order->account);

        // generate an invoice
        $invoice = $this->xeroInvoiceService->order($event->order, $contact);

        // store the invoice information
        $event->order->process_id = $invoice['InvoiceID'];
        $event->order->processed_at = \Carbon\Carbon::now();
        $event->order->admin_notes = ($event->order->admin_notes != '' ? $event->order->admin_notes . '<br/>' : $event->order->admin_notes) . 'Xero Invoice Number: ' . $invoice['InvoiceNumber'];
        $event->order->save();

        // now the payment. Only process payments that have been done online, or have a transaction_id.
        foreach ($event->order->payments as $payment) {
            if ($payment->transaction_id != '') {
                $payment = $this->xeroPaymentService->payment($payment, $event->order->process_id);
            }
        }

        info($event->order->id . ' was succesfully pushed to Xero with Xero invoice of ' . $invoice['InvoiceNumber']);
    }
}
