<?php

namespace AdminUI\AdminUIXero\Services;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\Order;

class XeroInvoiceService
{
    public static function order(Order $order, array $contact): array
    {
        // confirm the order is not empty
        if ($order->lines->count() <= 0) {
            return false;
        }

        foreach ($order->lines as $item) {
            $items[] = [
                'Description' => $item->product_name . '(' . $item->sku_code . ')',
                'Quantity' => $item->qty,
                'UnitAmount' => $item->item_exc_tax / 100,
                'LineAmount' => $item->line_exc_tax / 100,
                'TaxAmount' => $item->line_tax / 100,
                'AccountCode' => 200,
                'TaxType' => $item->tax_rate == 20 ? 'OUTPUT2' : 'NONE',
            ];
        }

        // postage
        $postage = $order->postageRate;
        if ($postage) {
            $items[] = [
                'Description' => $order->postage_description == '' ? $postage->postageType->name : $order->postage_description,
                'Quantity' => 1,
                'UnitAmount' => $order->postage_exc_tax / 100,
                'LineAmount' => $order->postage_exc_tax / 100,
                'TaxAmount' => $order->postage_tax / 100,
                'AccountCode' => 200,
                'TaxType' => $order->postage_exc_tax != $order->postage_inc_tax ? 'OUTPUT2' : 'NONE',
            ];
        }

        // delivery address
        $address = $order->billing;
        if ($order->delivery_address_id != $order->billing_address_id) {
            $address = $order->delivery;
        }
        if ($address) {
            $items[] = [
                'Description' => 'Delivery Address: ' . $address->addressee . ', ' . $address->address . ', ' . $address->address_2 . ', ' . $address->town . ', ' . $address->county . ', ' . $address->postcode . '; Tel: ' . $address->phone,
            ];
        }

        $due = \Carbon\Carbon::now()->addDays($order->account->payment_terms ?? 0)->format('Y-m-d');

        $data = [
            'Type' => 'ACCREC',
            'Contact' => [
                'ContactID' => $contact['ContactID'],
            ],
            'DueDate' => $due,
            'Reference' => 'MM/' . $order->id,
            'LineAmountTypes' => 'Exclusive',
            'LineItems' => $items ?? [],
            'Status' => 'AUTHORISED',
        ];
        return Xero::invoices()->store($data);
    }

    public static function clean(string $string): string
    {
        return strtolower(trim($string));
    }
}
