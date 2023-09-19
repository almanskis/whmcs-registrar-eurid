<?php

namespace Module\Registrar\Eurid\Controller;

require_once(__DIR__ . '/../../vendor/autoload.php');

use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use AgileGeeks\EPP\Eurid\Client AS EuridClient;
use AgileGeeks\EPP\Eurid\Exception AS EuridException;

class Domain
{
    /**
     * Check if domain(s) is(are) free to register
     * 
     * @param EuridClient $client
     * @param array $domains
     * @return object|mixed
     * @throws EuridException
     */
    public function check(EuridClient $client, array $domains): string
    {
        try {
            return $client->checkDomains($domains);
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $domains, 'domain_check');
        }
    }

    /**
     * Register the domain
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function create(EuridClient $client, array $requestData)
    {
        try {
            return $client->createDomain(
                $requestData['domain'],
                $requestData['period'],
                $requestData['registrant_cid'],
                $requestData['contact_tech_cid'],
                $requestData['contact_billing_cid'],
                $requestData['contact_onsite_cid'],
                $requestData['contact_reseller_cid'],
                $requestData['nameservers']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_create');
        }
    }

    /**
     * Get domain info from the registrar
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function getInfo(EuridClient $client, array $requestData)
    {
        try {
            return $client->domainInfo(
                $requestData['domain'],
                $requestData['authInfo'],
                $requestData['requestAuthInfo'],
                $requestData['cancelAuthInfo']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_get_info');
        }
    }

    /**
     * Renew the domain registration period
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function renew(EuridClient $client, array $requestData)
    {
        try {
            return $client->renewDomain(
                $requestData['domain'],
                $requestData['period'],
                $requestData['curExpDate']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_renew');
        }
    }

    /**
     * Request to transfer the domain to another registrar
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function requestTransfer(EuridClient $client, array $requestData)
    {
        try {
            return $client->domainTransferRequest(
                $requestData['domain'],
                $requestData['authInfo'],
                $requestData['period'],
                $requestData['cid'],
                $requestData['billing'],
                $requestData['tech'],
                $requestData['nameservers'],
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_request_transfer');
        }
    }

    /**
     * Get domain transfer details
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function queryTransfer(EuridClient $client, array $requestData)
    {
        try {
            return $client->domainTransferInfo(
                $requestData['domain']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_query_transfer');
        }
    }

    /**
     * Update domain nameservers
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function updateNameservers(EuridClient $client, array $requestData)
    {
        try {
            return $client->updateNameservers(
                $requestData['domain'],
                $requestData['add'],
                $requestData['rem']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_update_nameservers');
        }
    }

    /**
     * Request to delete the domain on specific date
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function requestDelete(EuridClient $client, array $requestData)
    {
        try {
            return $client->deleteDomain(
                $requestData['domain'],
                $requestData['deldate']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_request_delete');
        }
    }

    /**
     * Cancel the domain deletion operation
     * 
     * @param EuridClient $client
     * @param array $requestData
     * @return object|mixed
     * @throws EuridException
     */
    public static function cancelDelete(EuridClient $client, array $requestData)
    {
        try {
            return $client->undeleteDomain(
                $requestData['domain']
            );
        } catch (EuridException $e) {
            throw new EuridException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_cancel_delete');
        }
    }

    /**
     * @param array $nameservers
     * @param object $domain
     * @param string $putNsTo - 'key' | 'value'
     */
    public static function getNameserversFromInfo(array $nameservers, object $domain, $putNsTo = 'key'): array
    {
        $nameserverHostnames = array_keys($domain->nameservers);

        if ($putNsTo == 'value') {
            $index = 0;
            foreach ($nameservers as $key => $value) {
                $nameservers[$key] = $nameserverHostnames[$index];

                $index++;
            }

            return $nameservers;
        }

        return array_fill_keys($nameserverHostnames, []);
    }

    /**
     * @param array $currentNameservers
     * @param array $updateNameservers
     * @return array
     */
    public static function findAddedNameservers(array $currentNameservers, array $updatedNameservers): array
    {
        return array_diff_key($updatedNameservers, $currentNameservers);
    }

    /**
     * @param array $currentNameservers
     * @param array $updateNameservers
     * @return array
     */
    public static function findDeletedNameservers(array $currentNameservers, array $updatedNameservers): array
    {
        return array_diff_key($currentNameservers, $updatedNameservers);
    }

    /**
     * @param int $domainId
     * @return string
     */
    public static function getExpiryDate(int $domainId): string
    {
        return Capsule::table('tbldomains')
                    ->where('id', $domainId)
                    ->value('expirydate');
    }

    /**
     * @param int $domainId
     * @param string $date
     * @return void
     */
    public static function updateExpiryDate(int $domainId, string $date): void
    {
        $date = self::formatDate($date, "Y-m-d");

        self::updateLocalData($domainId, ['expirydate' => $date]);
    }

    /**
     * @param int $domainId
     * @param string $date
     * @return void
     */
    public static function updateDueDate(int $domainId, string $date): void
    {
        $date = self::formatDate($date, "Y-m-d");

        self::updateLocalData($domainId, ['nextduedate' => $date]);
    }

    /**
     * @param int $domainId
     * @param string $date
     * @return void
     */
    public static function updateNextInvoiceDate(int $domainId, string $date): void
    {
        $date = self::formatDate($date, "Y-m-d");

        self::updateLocalData($domainId, ['nextinvoicedate' => $date]);
    }

    /**
     * @param int $domainId
     * @param string $status
     * @return void
     */
    public static function updateStatus(int $domainId, string $status): void
    {
        $status = self::formatStatus($status);

        self::updateLocalData($domainId, ['status' => $status]);
    }

    /**
     * @param string $registrarStatus
     * @return string
     */
    public static function formatStatus(string $registrarStatus): string
    {
        $status = 'Active';

        if ($registrarStatus == 'pending') {
            $status = 'Pending Transfer';
        }

        return $status;
    }

    /**
     * @param array $nameservers
     * @return array
     */
    public static function formatNameservers(array $nameservers): array
    {
        $formatedNameservers = [];

        foreach ($nameservers as $nameserver) {
            if (empty($nameserver) === false) {
                $formatedNameservers[] = [$nameserver];
            }
        }

        return $formatedNameservers;
    }

    /**
     * ----- Private methods -----
     */

     /**
     * @param string $date
     * @param string $format
     * @return string
     */
    private function formatDate(string $date, string $format): string
    {
        $date = new DateTime($date);

        return $date->format($format);
    }

    /**
     * Update domain data localy
     * 
     * @param int $id
     * @param array $data
     * @return void
     */
    private function updateLocalData(int $id, array $data): void
    {
        Capsule::table('tbldomains')
            ->where('id', $id)
            ->update($data);
    }
}