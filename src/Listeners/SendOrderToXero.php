<?php

namespace AdminUI\AdminUIXero\Listeners;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Events\NewOrder;

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
        if ($xeroEnabled !== true) {
            return;
        }
        // Create a contact
        $where = [];
        $acCode = 'AUI' . $event->order->account->id;
        $where[] = 'AccountNumber="' . $acCode . '"';
        $owner = $event->order->account->owners()->first();
        if (!empty($owner)) {
            $where[] = 'EmailAddress="' .  $owner->email . '"';
        }
        dd($where);
        $contact = Xero::contacts()->get(1, implode("&", $where));

        if (!$contact) {
            // create a new contact
            $cdata = [
                'Name' => $event->order->account->name ?? 'Cash Sale'
            ];
            $contact = Xero::contacts()->store($cdata);
        }

        $data = [
            'Type' => 'ACCREC',
            'Contact' => [
                "Name" => $event->order->account->name ?? 'Cash Sale',
            ],
            'LineItems' => [
                [
                    'Description' => 'A product',
                    'Quantity' => '5',
                    'UnitAmount' => '30.27',
                    'LineAmount' => '151.35',
                    'TaxType' => 'CAPEXINPUT2'
                ], [
                    'Description' => 'A second product',
                    'Quantity' => '5',
                    'UnitAmount' => '30.00',
                    'LineAmount' => '150.00',
                    'TaxType' => 'CAPEXINPUT2'
                ],
            ],
            'Date' => date('Y-m-d'),
            "DueDate" => date('Y-m-d'),
            'InvoiceNumber' => $event->order->id,
            'SentToContact' => true,
            'Status' => 'AUTHORISED'
        ];
        Xero::invoices()->store($data);
    }
}
