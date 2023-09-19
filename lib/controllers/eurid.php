<?php

namespace Module\Registrar\Eurid\Controller;

use Illuminate\Database\Capsule\Manager as Capsule;
use AgileGeeks\EPP\Eurid\Client AS EuridClient;

require_once(__DIR__ . '/../../vendor/autoload.php');

class Eurid
{
    private const MODULE_NAME = 'EURid';

    private static $EURID_TABLE_CONTACTS = 'mod_eurid_contacts';

    /**
     * @param boolean $tryout
     */
    function __construct(bool $tryout = false)
    {
        if ($tryout === true) {
            self::$EURID_TABLE_CONTACTS .= '_tryout';
        }
    }

    /**
     * @return string
     */
    public function getEuridContactsTable(): string
    {
        return self::$EURID_TABLE_CONTACTS;
    }

    /**
     * @param array $requestData
     * @return EuridClient
     */
    public function login($requestData): EuridClient
    {
        $client = new EuridClient(
            $requestData['host'],
            $requestData['user'],
            $requestData['pass'],
            $requestData['debug'],
            $requestData['port'],
            $requestData['timeout'],
            $requestData['ssl'],
        );
        
        $client->login();

        return $client;
    }

    /**
     * @param string $clientId
     * @param string $contactId
     * @param string $type
     * @return void
     */
    public function saveContactLocaly(string $clientId, string $contactId, string $type): void
    {
        Capsule::table(self::$EURID_TABLE_CONTACTS)
            ->insert([
                'client_id'    => $clientId,
                'contact_id'   => $contactId,
                'contact_type' => $type
            ]);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function getDetails($params): array
    {
        $details = [];

        switch ($params['accountMode']) {
            case 'production':
                $details['host']           = $params['productionUrl'];
                $details['user']           = $params['productionUid'];
                $details['pass']           = $params['productionPassword'];
                $details['port']           = $params['productionPort'];
                $details['billingContact'] = $params['productionBilling'];
                $details['techContact']    = $params['productionTech'];
                break;
            
            case 'tryout':
                $details['host']           = $params['tryoutUrl'];
                $details['user']           = $params['tryoutUid'];
                $details['pass']           = $params['tryoutPassword'];
                $details['port']           = $params['tryoutPort'];
                $details['billingContact'] = $params['tryoutBilling'];
                $details['techContact']    = $params['tryoutTech'];
                break;

            default:
                return '';
                break;
        }

        $details['debug']   = $params['debug'] === 'on' ? true : false;
        $details['ssl']     = $params['ssl']   === 'on' ? true : false;
        $details['timeout'] = 15;
        $details['mode']    = $params['accountMode'];

        return $details;
    }

    /**
     * @param array $array
     * @return array
     */
    public static function removeEmptyKeyValues($array): array
    {
        return array_filter($array, function($key) { return !empty($key); }, ARRAY_FILTER_USE_KEY);
    }

    /** 
     * @param $daysInToFuture int
     * @return string
     */
    public static function formatFutureDate(int $daysInToFuture): string
    {
        return date('Y-m-d\TH:i:s.0\Z', strtotime(date('Y-m-d') . " +{$daysInToFuture} days"));
    }

    /**
     * Logs action in to activity logs
     * 
     * @param string @message
     */
    public static function logActivity(string $message): void
    {
        logActivity("EURid - {$message}");
    }

    /**
     * Logs action in to module logs
     * 
     * @param string $action
     * @param array $requestData
     * @param array $response
     * @param array $processedData
     */
    public static function logModuleActions($action, $requestData, $response = null, $processedData = null): void
    {
        logModuleCall(self::MODULE_NAME, $action, $requestData, $response, $processedData);
    }

    /**
     * Check if Contacts table exists and create if needed
     *
     */
    public function checkIfContactsTableExists(): void
    {
        if (!Capsule::schema()->hasTable(self::$EURID_TABLE_CONTACTS)) {
            Capsule::schema()->create(
                self::$EURID_TABLE_CONTACTS,
                function ($table) {
                    $table->engine = 'MyISAM';
                    $table->increments('id');
                    $table->integer('client_id')->comment('ID from tblclients');
                    $table->string('contact_id', 32)->comment('ID of EURid contact');
                    $table->enum('contact_type', ['billing', 'on-site', 'registrant', 'reseller', 'technical'])->comment('Type of EURid contact');
                    $table->timestamp('created_at')->nullable()->useCurrent();
                }
            );
        }
    }
}