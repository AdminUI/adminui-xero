<?php

namespace AdminUI\AdminUIXero\Resources;


use AdminUI\AdminUIXero\Facades\Xero;

class Payments extends Xero
{
    public function get(int $page = null, string $where = null)
    {
        $params = http_build_query([
            'page' => $page,
            'where' => $where
        ]);

        $result = Xero::get('Payments?' . $params);

        return $result['body']['Payments'];
    }

    public function find(string $paymentId)
    {
        $result = Xero::get('Payments/' . $paymentId);

        return $result['body']['Payments'][0];
    }

    public function apply(string $invoiceID, array $data)
    {
        $result = Xero::put('Payments', [
            "Invoice" => ["InvoiceID" => $invoiceID],
            "Account" => ["AccountID" => config('xero.accountId')],
            ...$data
        ]);

        return $result['body']['Payments'][0];
    }
}
