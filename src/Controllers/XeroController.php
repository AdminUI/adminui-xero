<?php

namespace AdminUI\AdminUIXero\Controllers;

use Inertia\Inertia;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Facades\Flash;
use Illuminate\Support\Facades\DB;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\Configuration;
use AdminUI\AdminUIXero\Models\XeroToken;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIXero\Services\XeroContactService;
use AdminUI\AdminUIXero\Services\XeroInvoiceService;
use AdminUI\AdminUIXero\Services\XeroPaymentService;
use AdminUI\AdminUI\Controllers\AdminUI\Inertia\InertiaCoreController;


class XeroController extends InertiaCoreController
{
    use ApiResponseTrait;

    public function __construct(
        public XeroContactService $xeroContactService,
        public XeroInvoiceService $xeroInvoiceService,
        public XeroPaymentService $xeroPaymentService
    ) {
    }

    public function index()
    {
        $this->seo([
            'title' => 'Xero Integration Setup'
        ]);
        return Inertia::render('xero::Setup', [
            'xeroSettings' => fn () => Configuration::where('section', 'xero')->get(),
            'xeroContacts' => fn () => Account::whereNull('import_id')->get(),
            'xeroTenant' => fn () => Xero::isConnected() ? Xero::getTenantName() : null,
            'xeroToken' => fn () => Xero::isConnected() ? XeroToken::select([
                'tenant_id', 'scopes', 'tenant_type', 'created_at', 'updated_at'
            ])->firstWhere('tenant_id', Xero::getTenantId()) : null,
            'xeroInvoices' => fn () => [],
        ]);
    }

    public function sync()
    {
        set_time_limit(0);
        Flash::success($synced . ' accounts were synced with quickbooks');
        return back();
    }


    public function manual($id)
    {
        $order = Order::where('id', $id)->whereNull('processed_at')->first();
        if (!$order) {
            die('order processed already');
        }

        // This will push the order to xero
        // Create or get new contact info
        if (!$order->account) {
            return false;
        }

        $contact = $this->xeroContactService->getContact($order->account);

        // generate an invoice
        $invoice = $this->xeroInvoiceService->order($order, $contact);

        // store the invoice information
        $order->process_id = $invoice['InvoiceID'];
        $order->processed_at = \Carbon\Carbon::now();
        $order->admin_notes = ($order->admin_notes != '' ? $order->admin_notes . '<br/>' : $order->admin_notes) . 'Xero Invoice Number: ' . $invoice['InvoiceNumber'];
        $order->save();

        // now the payment. Only process payments that have been done online, or have a transaction_id.
        // now the payment. Only process payments that have been done online.
        foreach ($order->payments as $payment) {
            if ($payment->transaction_id != '') {
                $payment = $this->xeroPaymentService->payment($payment, $order->process_id);
            }
        }

        echo 'All done. Check Invoice Number: ' . $invoice['InvoiceNumber'] . ' on Xero';
    }

    public static function clean(string $string): string
    {
        return strtolower(trim($string));
    }

    public function disconnect()
    {
        Xero::disconnect();
        DB::table('xero_tokens')->truncate();

        return $this->respondSuccess();
    }
}
