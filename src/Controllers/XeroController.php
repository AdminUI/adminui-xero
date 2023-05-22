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
        // $synced = $this->allocateAccounts();
        $synced = $this->allocateCompanies();
        Flash::success($synced . ' accounts were synced with quickbooks');
        return back();
    }

    public static function allocateAccounts()
    {
        die;
        // $accounts = Account::whereNull('import_id')->with('owners')->orderBy('id', 'desc')->get();
        // $syncing = $accounts->count();
        // $synced = 0;
        // if ($accounts->count() > 0) {
        //     foreach ($accounts as $account) {
        //         $user = $account->owners->first();
        //         if ($user) {
        //             // check if on xero
        //             $where = 'EmailAddress="' . $user->email . '"';
        //             $contacts = Xero::contacts()->get(1, $where);
        //             // // if not on xero
        //             if ($contacts) {
        //                 foreach ($contacts as $contact) {
        //                     if (trim($account->name) == trim($contact['Name'])) {
        //                         $account->import_id = $contact['ContactID'];
        //                         $account->save();
        //                         $synced++;
        //                     }
        //                 }
        //             } else {
        //                 $account->import_id = 0;
        //                 $account->save();
        //             }
        //         }
        //     }
        // }
        return $synced;
    }

    public static function allocateCompanies()
    {
        $accounts = Account::whereNull('import_id')->with('owners')->orderBy('id', 'desc')->paginate('30');
        $syncing = $accounts->count();
        $synced = 0;
        if ($accounts->count() > 0) {
            foreach ($accounts as $account) {
                self::checkAccount($account);
            }
        }
        return $synced;
    }

    public function checkAccount($account)
    {
        if ($account->name != '') {
            // check if on xero
            $where = 'Name="' . trim($account->name) . '"';
            $contacts = Xero::contacts()->get(1, $where);
            if ($contacts) {
                $user = $account->owners()->first();
                if (empty($user)) {
                    $user = $account->users()->first();
                }
                if ($user) {
                    foreach ($contacts as $contact) {
                        if (self::clean($user->email)  == self::clean($contact['EmailAddress'])) {
                            $account->import_id = $contact['ContactID'];
                            $account->save();
                            $synced++;
                            break;
                        } else {
                            $account->import_id = 1;
                            $account->save();
                        }
                    }
                } else {
                    $account->import_id = 0;
                    $account->save();
                }
            } else {
                $account->import_id = 2;
                $account->save();
            }
        }
    }

    public function pushOrderToXero($order)
    {
        // This will push the order to xero
        // Create or get new contact info
        $contact = $this->xeroContactService->getContact($order->account);

        // now you have a contact create invoice
        $invoice = $this->xeroInvoiceService->order($order, $contact);

        // store the invoice information
        $order->process_id = $invoice['InvoiceID'];
        $order->processed_at = \Carbon\Carbon::now();
        $order->admin_notes = $order->admin_note . '<br/> Xero Invoice Number: ' . $invoice['InvoiceNumber'];
        $order->save();

        // now the payment. Only process payments that have been done online.
        foreach ($order->payments as $payment) {
            if ($payment->transaction_id != '') {
                $payment = $this->xeroPaymentService->payment($order);
            }
        }

        return true;
        // All done hopefully
    }

    public function manual($id)
    {
        $order = Order::where('id', $id)->whereNull('processed_at')->first();
        if (!$order) {
            die('order processed already');
        }

        // find the correct contact
        $contact = $this->xeroContactService->getContact($order->account);

        // generate an invoice
        $invoice = $this->xeroInvoiceService->order($order, $contact);

        // store the invoice information
        $order->process_id = $invoice['InvoiceID'];
        $order->processed_at = \Carbon\Carbon::now();
        $order->admin_notes = $order->admin_note . '<br/> Xero Invoice Number: ' . $invoice['InvoiceNumber'];
        $order->save();

        // now the payment. Only process payments that have been done online, or have a transaction_id.
        foreach ($order->payments as $payment) {
            if ($payment->transaction_id != '') {
                $payment = $this->xeroPaymentService->payment($order);
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
