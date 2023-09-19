<?php

namespace Module\Registrar\Eurid\Controller;

use Illuminate\Database\Capsule\Manager as Capsule;
use Module\Registrar\Eurid\Controller\Eurid;
use AgileGeeks\EPP\Eurid\Client AS EuridClient;
use AgileGeeks\EPP\Eurid\Exception AS EuridException;

require_once(__DIR__ . '/../../vendor/autoload.php');

class Contact
{
    /**
     * Create new contact
     * 
     * @param EuridClient $client
     * @param array contact data
     * @return object|mixed
     * @throws EuridException
     */
    public static function create(EuridClient $client, array $requestData)
    {
        try {
            return $client->createContact(
                $requestData['name'],
                $requestData['organization'],
                $requestData['street1'],
                $requestData['street2'],
                $requestData['street3'],
                $requestData['city'],
                $requestData['state_province'],
                $requestData['postal_code'],
                $requestData['country_code'],
                $requestData['phone'],
                $requestData['fax'],
                $requestData['email'],
                $requestData['natural_person'],
                $requestData['contact_type']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'contact_create');
        }
    }

    /**
     * Get contact info
     * 
     * @param EuridClient $client
     * @param string @contactId
     * @return object|mixed
     * @throws EuridException
     */
    public static function getInfo(EuridClient $client, string $contactId)
    {
        try {
            return $client->contactInfo($contactId);
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), ['id' => $contactId], 'contact_get_info');
        }
    }

    /**
     * Update contact details
     * 
     * @param EuridClient $client
     * @param array @requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function update(EuridClient $client, array $requestData)
    {
        try {
            return $client->updateContact(
                $requestData['id'],
                $requestData['name'],
                $requestData['company'],
                $requestData['street1'],
                $requestData['street2'],
                $requestData['street3'],
                $requestData['city'],
                $requestData['state'],
                $requestData['postcode'],
                $requestData['country'],
                $requestData['phone'],
                $requestData['fax'],
                $requestData['email'],
                $requestData['natural'],
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'contact_update');
        }
    }

    /**
     * Get Eurid registrant contact id of WHMCS user with id of $clientId
     * 
     * @param string $clientId
     * @param Eurid $eurid
     * @return string registrant id | empty string
     */
    public static function getRegistrantId(string $clientId, Eurid $eurid): string
    {
        return self::getClientTypeId($clientId, 'registrant', $eurid);
    }

    /**
     * Format details for contact creation
     * 
     * @param array $params
     * @return array formated contact data
     */
    public static function formatDetailsForCreate(array $params): array
    {
        $companyName   = '';
        $naturalPerson = 'true';

        if ($params['fullname'] !== $params['companyname'] && empty($params['companyname']) === false)
        {
            $companyName   = $params['companyname'];
            $naturalPerson = 'false';
        }

        return [
            'name'           => $params['fullname'],
            'organization'   => $companyName,
            'street1'        => $params['address1'],
            'street2'        => $params['address2'],
            'street3'        => '',
            'city'           => $params['city'],
            'state_province' => $params['state'],
            'postal_code'    => $params['postcode'],
            'country_code'   => $params['countrycode'],
            'phone'          => $params['phonenumberformatted'],
            'fax'            => '',
            'email'          => $params['email'],
            'natural_person' => $naturalPerson,
            'contact_type'   => 'registrant'
        ];
    }

    /**
     * Format details for contact update
     * 
     * @param string id
     * @param array $details
     * @return array
     */
    public static function formatDetailsforUpdate(string $id, array $details): array
    {
        $natural_person = empty($details['Company Name']) ? 'true' : 'false';
        $name           = $details['Name'] ??  $details['Full Name'];

        return [
            'id'       => $id,
            'name'     => $name,
            'company'  => $details['Company Name'],
            'street1'  => $details['Address 1'],
            'street2'  => $details['Address 2'],
            'street3'  => '',
            'city'     => $details['City'],
            'state'    => $details['State'],
            'postcode' => $details['Postcode'],
            'country'  => $details['Country'],
            'phone'    => $details['Phone Number'],
            'fax'      => '',
            'email'    => $details['Email Address'],
            'natural'  => $natural_person
        ];
    }

    /**
     * Format contact details from eurid for whmcs contact display
     * 
     * @param object @details
     * @return array
     */
    public static function formatDetailsForDisplay(object $details): array
    {
        return [
            'Name'          => $details->name,
            'Company Name'  => $details->org,
            'Email Address' => $details->email,
            'Address 1'     => $details->street[0],
            'Address 2'     => $details->street[1],
            'City'          => $details->city,
            'State'         => $details->sp,
            'Postcode'      => $details->pc,
            'Country'       => $details->cc,
            'Phone Number'  => $details->voice,
        ];
    }

    /**
     * ----- Private methods -----
     */

    /**
     * @param string $clientId
     * @param string $type
     * @param Eurid $eurid
     * @return string
     */
    private function getClientTypeId(string $clientId, string $type, Eurid $eurid): string
    {
        $eurid->checkIfContactsTableExists();

        $contactId = Capsule::table($eurid->getEuridContactsTable())
                        ->where('client_id', $clientId)
                        ->where('contact_type', $type)
                        ->value('contact_id');

        return $contactId ?? '';
    }
}