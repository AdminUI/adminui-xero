<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Traits\CliTrait;
use Facades\AdminUI\AdminUIXero\Controllers\XeroContactClass;
use Facades\AdminUI\AdminUIXero\Controllers\XeroInvoiceClass;
use Facades\AdminUI\AdminUIXero\Controllers\XeroPaymentClass;


class PushStripe extends Command
{
    use CliTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:pushtoxero';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push unprocessed orders to Xero';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(900);

        $orders = Order::whereNull('processed_at')
            ->whereNotIn('order_status_id', [9, 17])
            ->get();

        $started = microtime(true);
        $this->cliInfo('Pushing ' . $orders->count() . ' orders to XERO, Please wait...');
        $this->cliStart();

        $this->cliProgressStart($orders->count());

        foreach ($orders as $order) {

            $contact = XeroContactClass::getContact($order->account);

            // now you have a contact create invoice
            $invoice = XeroInvoiceClass::order($order, $contact);

            // store the invoice information
            $order->process_id = $invoice['InvoiceID'];
            $order->processed_at = \Carbon\Carbon::now();
            $order->admin_notes = ($order->admin_notes != '' ? $order->admin_notes . '<br/>' : $order->admin_notes) . 'Xero Invoice Number: ' . $invoice['InvoiceNumber'];
            $order->save();


            // now the payment. Only process payments that have been done online.
            foreach ($order->payments as $payment) {
                if ($payment->transaction_id != '') {
                    $payment = XeroPaymentClass::payment($payment, $order->process_id);
                }
            }
            $this->cliProgress();
            sleep(1);
        }
        $this->cliFinish('All done.');
        $this->cliInfo('Finished');

        return Command::SUCCESS;
    }
}
