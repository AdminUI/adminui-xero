<?php

namespace AdminUI\AdminUIXero\Controllers;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\Payment;

class XeroPaymentClass
{
    public static function payment(Payment $payment, String $process_id = null)
    {
        $paymentData = [
            'Date'      => $payment->created_at->format('Y-m-d'),
            'Amount'    => $payment->total / 100,
            'Reference' => $payment->transaction_id
        ];
        return Xero::payments()->apply($order->process_id, $paymentData);
    }
}
