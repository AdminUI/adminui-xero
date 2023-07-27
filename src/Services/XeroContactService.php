<?php

namespace AdminUI\AdminUIXero\Services;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\Account;
use Str;

class XeroContactService
{

    public static function getContact(Account $account)
    {
        // get existing account or create a new one
        $contact = self::getContactById($account->import_id);

        // ensure the contact account is not archived
        if ($contact && $contact['ContactStatus'] == 'ARCHIVED') {
            // $this->cliInfo("Contact has been archived on Xero. Regenerating the contact");
            $account->import_id = null;
            $account->save();
            unset($contact);
        }

        // if a valid contact return it
        if ($contact) {
            return $contact;
        }

        // did nt have a matching contactId so try to find using email
        $user = self::getUser($account);
        if ($user) {
            $contact = self::getContactByEmail($user->email);
        }
        if ($contact) {
            if (count($contact) == 1) {
                self::saveContact($contact[0], $account);
                return $contact[0];
            }
        }

        // did nt have a matching email , try to match account name
        $contact = self::getContactByName(self::clean($account->name));
        if ($contact) {
            if (count($contact) == 1) {
                self::saveContact($contact[0], $account);
                return $contact[0];
            }
        }

        // definitely does not exist, create a new contact
        return self::createContact($account, $user);
    }

    public static function getUser(Account $account)
    {
        $user = $account->owners()->first();
        if (!empty($user)) {
            return $user;
        }
        $user = $account->users()->first();
        if (!empty($user)) {
            return $user;
        }
        return false;
    }

    public static function getContactById($id)
    {
        if (in_array($id, [0, 1, 2, 3, null])) {
            return false;
        }
        return Xero::contacts()->find($id);
    }

    public static function getContactByEmail($email)
    {
        return Xero::contacts()->get(1, 'EmailAddress="' . self::clean($email) . '"');
    }

    public static function getContactByName($name)
    {
        return Xero::contacts()->get(1, 'Name="' . self::clean($name) . '"');
    }

    public static function createContact($account, $user)
    {
        $addresses = $account->addresses;
        if ($addresses) {
            foreach ($addresses as $address) {
                $add[] = [
                    'AddressType' => $address->is_billing ? 'POBOX' : 'STREET',
                    'AddressLine1' => $address->addressee ?? $account->name,
                    'AddressLine2' => $address->address ?? '',
                    'AddressLine3' => $address->address_2 ?? '',
                    'City' => $address->town ?? '',
                    'Region' => $address->county ?? '',
                    'PostalCode' => $address->postcode,
                    'Country' => $address->country->name ?? 'United Kingdom'
                ];
            }
        }

        $contact = Xero::contacts()->store([
            'Name' => $account->name,
            'ContactNumber' => 'AUI' . $account->id,
            'EmailAddress' => $user->email ?? 'noemail@' . Str::slug($account->name) . 'co.uk',
            'FirstName' => $user->first_name ?? $account->name,
            'LastName' => $user->last_name ?? '',
            'TaxNumber' => $account->tax_number,
            'Addresses' => $add ?? [],
            'Phones' => [
                [
                    'PhoneType' => 'DEFAULT',
                    'PhoneNumber' => $user->phone ?? '0',
                ],
            ],
            'PaymentTerms' => [
                'DAYSAFTERBILLDATE' => $account->payment_days ?? 0
            ]
        ]);
        self::saveContact($contact, $account);
        sleep(2);
        return $contact;
    }

    public static function saveContact($contact, $account)
    {
        $account->import_id = $contact['ContactID'];
        $account->save();
    }

    public static function clean(string $string): string
    {
        return strtolower(trim($string));
    }
}
