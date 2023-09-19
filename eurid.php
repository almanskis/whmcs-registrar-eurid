<?php
/**
 * WHMCS EURid registrar module for .EU domains registration
 *
 * @copyright Copyright (c) BaCloud 2023
 * @author Almantas Girskis almantas.girskis@ist.lt
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . '/lib/controllers/autoload.php');

use Module\Registrar\Eurid\Controller\{Contact, Domain, Eurid};

/**
 * Define module related metadata
 *
 * @return array
 */
function eurid_MetaData()
{
    return [
        'DisplayName' => 'EURid (EU domains)',
        'APIVersion'  => '1.0',
    ];
}

/**
 * Define registrar configuration options.
 *
 * @return array
 */
function eurid_getConfigArray()
{
    return [
        'productionUrl' => [
            'FriendlyName' => 'Production URL',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => 'epp.registry.eu',
            'Description'  => 'Production URL. Default is epp.registry.eu',
        ],
        'productionUid' => [
            'FriendlyName' => 'Production UID',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Production user ID (not username). eg. a012345',
        ],
        'productionPassword' => [
            'FriendlyName' => 'Production EPP Password',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Production EPP password',
        ],
        'productionPort' => [
            'FriendlyName' => 'Production Port',
            'Type'         => 'text',
            'Default'      => '700',
            'Description'  => 'Production connection port. Default 700',
        ],
        'productionBilling' => [
            'FriendlyName' => 'Production Billing contact',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Production billing contact. eg. c1111111',
        ],
        'productionTech' => [
            'FriendlyName' => 'Production Technical contact',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Production technical contact. eg. c1111111',
        ],
        'tryoutUrl' => [
            'FriendlyName' => 'Tryout URL',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => 'epp.tryout.registry.eu',
            'Description'  => 'Tryout URL. Default is epp.tryout.registry.eu',
        ],
        'tryoutUid' => [
            'FriendlyName' => 'Tryout UID',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Tryout user ID (not username). eg. t012345',
        ],
        'tryoutPassword' => [
            'FriendlyName' => 'Tryout EPP Password',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Tryout EPP password',
        ],
        'tryoutPort' => [
            'FriendlyName' => 'Tryout Port',
            'Type'         => 'text',
            'Default'      => '700',
            'Description'  => 'Tryout connection port. Default 700',
        ],
        'tryoutBilling' => [
            'FriendlyName' => 'Tryout Billing contact',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Tryout billing contact. eg. c1111111',
        ],
        'tryoutTech' => [
            'FriendlyName' => 'Tryout Technical contact',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Tryout technical contact. eg. c1111111',
        ],
        'debug' => [
            'FriendlyName' => 'Debug Mode',
            'Type'         => 'yesno',
            'Description'  => 'Tick to enable debug',
        ],
        'ssl' => [
            'FriendlyName' => 'SSL',
            'Type'         => 'yesno',
            'Description'  => 'Tick to use SSL',
        ],
        'accountMode' => [
            'FriendlyName' => 'Account Mode',
            'Type'         => 'dropdown',
            'Options'      => [
                'production' => 'Production',
                'tryout'     => 'Tryout',
            ],
            'Description' => 'Account mode to work with',
        ],
    ];
}

/**
 * Admin Area Custom Button Array.
 *
 * @return array
 */
function eurid_AdminCustomButtonArray() {
    return [
        'Cancel Delete Request' => 'CancelDeleteRequest',
        'Sync'                  => 'Sync',
        'Cancel EPP Code'       => 'CancelEPPCode',
    ];
}

/**
 * Client Area Custom Button Array.
 *
 * @return array
 */
function eurid_ClientAreaCustomButtonArray()
{
    return [];
}

/**
 * Client Area Allowed Functions.
 *
 * @return array
 */
function eurid_ClientAreaAllowedFunctions()
{
    return [];
}

/**
 * Register a domain.
 *
 * @return array with an error message or a success
 */
function eurid_RegisterDomain($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'domain_register';

    $action      = $function;
    $requestData = $params;

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $client      = $eurid->login($requestData);

        $registrantId = Contact::getRegistrantId($params['userid'], $eurid);

        if (empty($registrantId)) {
            $action = "{$function}__contact_create";

            $requestData = Contact::formatDetailsForCreate($params);

            $response = Contact::create($client, $requestData);

            Eurid::logModuleActions($action, $requestData, $response);

            $registrantId = (string)$response;

            $eurid->saveContactLocaly($params['userid'], $registrantId, 'registrant');
        }

        $action      = "{$function}__create_domain";
        $nameservers = Domain::formatNameservers([$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']]);

        $requestData = [
            'domain'               => $params['domain'],
            'period'               => $params['regperiod'],
            'registrant_cid'       => $registrantId,
            'contact_billing_cid'  => $registrarDetails['billingContact'],
            'contact_tech_cid'     => $registrarDetails['techContact'],
            'contact_onsite_cid'   => '',
            'contact_reseller_cid' => '',
            'nameservers'          => $nameservers
        ];

        $response = Domain::create($client, $requestData);
        Eurid::logModuleActions($action, $requestData, $response);

        Domain::updateDueDate($params['domainid'], $response->exDate);
        Domain::updateNextInvoiceDate($params['domainid'], $response->exDate);

        $client->logout();

    } catch (Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Initiate domain transfer.
 *
 * @return array
 */
function eurid_TransferDomain($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'domain_transfer';

    $action      = $function;
    $requestData = $params;

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $client      = $eurid->login($requestData);

        $registrantId = Contact::getRegistrantId($params['userid'], $eurid);

        if (empty($registrantId)) {
            $action = "{$function}__contact_create";

            $requestData = Contact::formatDetailsForUpdate($registrantId, $params);

            $response = Contact::create($client, $requestData);

            Eurid::logModuleActions($action, $requestData, $response);

            $registrantId = (string)$response;

            $eurid->saveContactLocaly($params['userid'], $registrantId, 'registrant');
        }

        $action      = "{$function}__request_transfer";
        $nameservers = Domain::formatNameservers([$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']]);

        $requestData = [
            'domain'      => $params['domain'],
            'authInfo'    => $params['eppcode'],
            'period'      => 0,
            'cid'         => $registrantId,
            'billing'     => $registrarDetails['billingContact'],
            'tech'        => $registrarDetails['techContact'],
            'nameservers' => $nameservers,
        ];

        $response = Domain::requestTransfer($client, $requestData);
        Eurid::logModuleActions($action, $requestData, $response);

        $domainStatus = Domain::formatStatus($response->trStatus);

        Domain::updateStatus($params['domainid'], $domainStatus);
        Domain::updateExpiryDate($params['domainid'], $response->exDate);
        Domain::updateDueDate($params['domainid'], $response->exDate);
        Domain::updateNextInvoiceDate($params['domainid'], $response->exDate);

        $client->logout();

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Renew a domain.
 *
 * @return array
 */
function eurid_RenewDomain($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'domain_renew';

    $action      = $function;
    $requestData = $params;

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $client      = $eurid->login($requestData);

        $action      = "{$function}__renew";
        $requestData = [
            'domain'     => $params['domain'],
            'period'     => $params['regperiod'],
            'curExpDate' => Domain::getExpiryDate($params['domainid'])
        ];
        $response = Domain::renew($client, $requestData);
        Eurid::logModuleActions($action, $requestData, $response);

        Domain::updateDueDate($params['domainid'], $response->exDate);
        Domain::updateNextInvoiceDate($params['domainid'], $response->exDate);

        $client->logout();

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Fetch current nameservers.
 *
 * @return array
 */
function eurid_GetNameservers($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'get_nameservers';

    $action      = $function;
    $requestData = $params;

    $nameservers = [
        'ns1' => '',
        'ns2' => '',
        'ns3' => '',
        'ns4' => '',
        'ns5' => ''
    ];

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];

        $domainInfo  = Domain::getInfo($client, $requestData);
        $nameservers = Domain::getNameServersFromInfo($nameservers, $domainInfo, 'value');

        Eurid::logModuleActions($action, $requestData, $domainInfo);

        $client->logout();

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return $nameservers;
}

/**
 * Save nameserver changes.
 *
 * @return array
 */
function eurid_SaveNameservers($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'save_nameservers';

    $action      = $function;
    $requestData = $params;

    $current_nameservers = [
        '' => [],
        '' => [],
        '' => [],
        '' => [],
        '' => []
    ];

    $updated_nameservers = [
        $params['ns1'] => [],
        $params['ns2'] => [],
        $params['ns3'] => [],
        $params['ns4'] => [],
        $params['ns5'] => []
    ];

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];

        $domainInfo          = Domain::getInfo($client, $requestData);
        $current_nameservers = Domain::getNameServersFromInfo($current_nameservers, $domainInfo);

        $current_nameservers = Eurid::removeEmptyKeyValues($current_nameservers);
        $updated_nameservers = Eurid::removeEmptyKeyValues($updated_nameservers);

        $action      = "{$function}__update";
        $requestData = [
            'domain' => $params['domain'],
            'add'    => Domain::findAddedNameservers($current_nameservers, $updated_nameservers),
            'rem'    => Domain::findDeletedNameservers($current_nameservers, $updated_nameservers)
        ];

        $response = Domain::updateNameservers($client, $requestData);
        Eurid::logModuleActions($action, $requestData, $response);

        $client->logout();

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Get the current WHOIS Contact Information.
 *
 * All EURid domains have the same Billing and Technical account.
 * For that reason, those contacts are not shown on contact details page.
 *
 * @return array
 */
function eurid_GetContactDetails($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'get_contact_details';

    $action      = $function;
    $requestData = $params;

    $contactsDetails = [];

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];
        $domainInfo   = Domain::getInfo($client, $requestData);
        $registrantId = $domainInfo->contacts['registrant'];

        $action                        = "{$function}__contact_info";
        $registrantDetails             = Contact::getInfo($client, $registrantId);
        $contactsDetails['Registrant'] = Contact::formatDetailsForDisplay($registrantDetails);
        Eurid::logModuleActions($action, $registrantId, $registrantDetails);

        $client->logout();

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return $contactsDetails;
}

/**
 * Update the WHOIS Contact Information for a given domain.
 * 
 * All EURid domains have the same Billing and Technical account.
 * For that reason, those contacts are not shown on contact details page.
 * And you can not update them from WHMCS contact details page.
 *
 * @return array
 */
function eurid_SaveContactDetails($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'save_contact_details';

    $action      = $function;
    $requestData = $params;

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];
        $domainInfo   = Domain::getInfo($client, $requestData);
        $registrantId = $domainInfo->contacts['registrant'];

        $action      = "{$function}__contact_update";
        $requestData = Contact::formatDetailsForUpdate($registrantId, $params['contactdetails']['Registrant']);
        $response    = Contact::update($client, $requestData);

        Eurid::logModuleActions($action, $requestData, $response);

        $client->logout();

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $params, $e->getMessage());

        return ['error' => $e->getMessage()];
    }
}

/**
 * Request EEP Code.
 *
 * @return array
 */
function eurid_GetEPPCode($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'get_epp_code';

    $action      = $function;
    $requestData = $params;

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthCode'  => '',
        ];

        $domainInfo = Domain::getInfo($client, $requestData);

        if (empty($domainInfo->authPW) && empty($domainInfo->authValidUntil)) {
            $action                         = "{$function}__request_epp";
            $requestData['requestAuthInfo'] = true;
            $domainInfo                     = Domain::getInfo($client, $requestData);
            Eurid::logActivity("Epp code was generated for the domain '{$requestData['domain']}'.");
        }

        Eurid::logModuleActions($action, $requestData, $domainInfo);

        $client->logout();

        if (empty($domainInfo->authPW) === false) {
            return ['eppcode' => $domainInfo->authPW];
        }
    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => 'success'];
}

/**
 * Cancel EEP Code.
 *
 * @return array
 */
function eurid_CancelEPPCode($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'cancel_epp_code';

    $action      = $function;
    $requestData = $params;

    try {
        $error = '';
        
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];
        $domainInfo = Domain::getInfo($client, $requestData);

        if (
            empty($domainInfo->authPW) === false 
            && empty($domainInfo->authValidUntil) === false
        ) {
            $action                        = "{$function}__cancel_epp_code";
            $requestData['cancelAuthInfo'] = true;
            $domainInfo                    = Domain::getInfo($client, $requestData);

            Eurid::logActivity("Epp code of the domain '{$requestData['domain']}' was cancelled.");
        } else {
            $error = 'Domain has no active EPP code.';
        }

        Eurid::logModuleActions($action, $requestData, $domainInfo);

        $client->logout();

        if (empty($error) === false) {
            return ['error' => $error];
        }
    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => 'success'];
}

/**
 * Delete Domain.
 *
 * @return array
 */
function eurid_RequestDelete($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'request_delete';

    $action      = $function;
    $requestData = $params;

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];
        $domainInfo = Domain::getInfo($client, $requestData);

        if (empty($domainInfo->delDate) === false) {
            Eurid::logModuleActions($action, $requestData, $domainInfo);

            return [
                'error' => "Domain already has a scheduled delete for {$domainInfo->delDate}."
            ];
        }

        $action      = "{$function}__request_delete";
        $requestData = [
            'domain'  => $params['domain'],
            'deldate' => Eurid::formatFutureDate(7)
        ];
        $response = Domain::requestDelete($client, $requestData);

        $client->logout();

        Eurid::logModuleActions($action, $requestData, $response);
        Eurid::logActivity("Domain '{$params['domain']}' was scheduled to be deleted on {$requestData['deldate']}");

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => 'success'];
}

/**
 * Cancel domain deletion.
 *
 * @return array
 */
function eurid_CancelDeleteRequest($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'cancel_delete';

    $action      = $function;
    $requestData = $params;

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];
        $domainInfo = Domain::getInfo($client, $requestData);

        if (empty($domainInfo->delDate)) {
            $client->logout();

            Eurid::logModuleActions($action, $requestData, $domainInfo);

            return [
                'error' => "Domain has no scheduled delete date."
            ];
        }

        $action      = "{$function}__cancel_delete";
        $requestData = ['domain' => $params['domain']];
        $response    = Domain::cancelDelete($client, $requestData);

        $client->logout();

        Eurid::logModuleActions($action, $requestData, $response);
        Eurid::logActivity("Deletion of the domain '{$params['domain']}' was cancelled.");
    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * @return array
 */
function eurid_Sync($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'sync';

    $action      = $function;
    $requestData = $params;

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = [
            'domain'          => $params['domain'],
            'authInfo'        => '',
            'requestAuthInfo' => '',
            'cancelAuthInfo'  => '',
        ];
        $domainInfo = Domain::getInfo($client, $requestData);
        Eurid::logModuleActions($action, $requestData, $domainInfo);

        Domain::updateExpiryDate($params['domainid'], $domainInfo->exDate);
        Domain::updateDueDate($params['domainid'], $domainInfo->exDate);
        Domain::updateNextInvoiceDate($params['domainid'], $domainInfo->exDate);
        Domain::updateStatus($params['domainid'], $domainInfo->status);

        $client->logout();

        return [
            'success' => true,
            'message' => '(Warning) You must refresh the page to see the changes'
        ];

    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }
}

/**
 * Incoming Domain Transfer Sync.
 *
 * Check status of incoming domain transfers and notify end-user upon
 * completion. This function is called daily for incoming domains.
 *
 * @return array
 */
function eurid_TransferSync($params)
{
    $registrarDetails = Eurid::getDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        Eurid::logActivity($message);

        return ['error' => $message];
    }

    $eurid = new Eurid($registrarDetails['mode'] == 'tryout');

    $function = 'transfer_sync';

    $action      = $function;
    $requestData = $params;

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $client      = $eurid->login($requestData);

        $action      = "{$function}__query_transfer";
        $requestData = [
            'domain' => $params['domain']
        ];
        $tranferInfo = Domain::queryTransfer($client, $requestData);

        Eurid::logModuleActions($action, $requestData, $tranferInfo);

        $client->logout();

        if ($tranferInfo->trStatus === 'serverApproved') {
            return [
                'completed'  => true,
                'expirydate' => $tranferInfo->exDate
            ];
        }
    } catch (\Exception $e) {
        Eurid::logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return [];
}

/**
 * Client Area Output.
 *
 * @return string HTML Output
 */
function eurid_ClientArea($params)
{
    return '';
}
