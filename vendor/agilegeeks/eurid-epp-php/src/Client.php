<?php

namespace AgileGeeks\EPP\Eurid;

use AgileGeeks\EPP\EPP_Client;
use AgileGeeks\EPP\EPP_Exception;
use AgileGeeks\EPP\Eurid\Frame;
use AgileGeeks\EPP\Eurid\Response;
use AgileGeeks\EPP\Eurid\Frames\Greeting;
use AgileGeeks\EPP\Eurid\Frames\Login;
use AgileGeeks\EPP\Eurid\Frames\Logout;
use AgileGeeks\EPP\Eurid\Frames\DomainCheck;
use AgileGeeks\EPP\Eurid\Frames\ContactCreate;
use AgileGeeks\EPP\Eurid\Frames\ContactUpdate;
use AgileGeeks\EPP\Eurid\Frames\DomainCreate;
use AgileGeeks\EPP\Eurid\Frames\ContactInfo;
use AgileGeeks\EPP\Eurid\Frames\DomainInfo;
use AgileGeeks\EPP\Eurid\Frames\DomainUpdateNS;
use AgileGeeks\EPP\Eurid\Frames\DomainRenew;
use AgileGeeks\EPP\Eurid\Frames\DomainDelete;
use AgileGeeks\EPP\Eurid\Frames\DomainUndelete;
use AgileGeeks\EPP\Eurid\Frames\DomainTransfer;
use AgileGeeks\EPP\Eurid\Frames\DomainTransferInfo;
use AgileGeeks\EPP\Eurid\Frames\CheckBalance;
use AgileGeeks\EPP\Eurid\Frames\DomainUpdateDNSSEC;
use Module\Registrar\Eurid\Controller\Eurid;

require_once(__DIR__ . '/Eurid/Frames/autoload.php');
require_once(__DIR__ . '/Eurid/Frame.php');
require_once(__DIR__ . '/EPP/Client.php');
require_once(__DIR__ . '/Eurid/Response.php');
require_once(__DIR__ . '/../../../../lib/controllers/eurid.php');

class Client extends EPP_Client
{
    private $connected;
    private $logged_in;
    private $user;
    private $pass;
    private $result;

    private $debug;
    private $greeting;

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param boolean $debug
     * @param integer $port
     * @param integer timeout
     * @param boolean $ssl
     * @param resource $context
     * @throws Net_EPP_Exception
     */
    function __construct($host = NULL, $user = NULL, $pass = NULL, $debug = false, $port = 700, $timeout = 1, $ssl = true, $context = NULL)
    {
        $this->connected = false;
        $this->logged_in = false;
        $this->debug = $debug;
        $this->user = $user;
        $this->pass = $pass;

        if ($host) {
            try {
                $this->connect($host, $port, $timeout, $ssl, $context);
            } catch (EPP_Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    function getResult()
    {
        return $this->result;
    }

    function greeting()
    {
        $command = new Greeting();
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function login()
    {
        $this->debug("Attempting to login");

        $command = new Login($this->user, $this->pass);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function checkDomains($domains)
    {
        $this->debug("Checking domains");

        $command = new DomainCheck($domains);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function checkHosts($host)
    { }

    function checkContacts($contacts)
    { }

    function checkBalance()
    {
        $this->debug("Getting balance info");

        $command = new CheckBalance();
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function domainInfo($domain, $authInfo = NULL, $requestAuthInfo = false, $cancelAuthInfo = false)
    {
        $this->debug("Getting domain details");

        $command = new DomainInfo($domain, $authInfo, $requestAuthInfo, $cancelAuthInfo);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function hostInfo($host, $authInfo = NULL)
    { }

    function contactInfo($contact, $authInfo = NULL)
    {
        $this->debug("Getting contact details");

        $command = new ContactInfo($contact);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function domainTransferQuery($domain)
    { }

    function domainTransferCancel($domain)
    { }

    function domainTransferRequest($domain, $authInfo, $period, $cid, $billing, $tech, $nameservers, $unit = 'y')
    {
        $this->debug("Requesting domain transfer");

        $command = new DomainTransfer(
            $domain,
            $authInfo,
            $period,
            $cid,
            $billing,
            $tech,
            $nameservers,
            $unit
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    function domainTransferApprove($domain)
    { }

    function domainTransferReject($domain)
    { }

    function domainTransferInfo($domain)
    {
        $this->debug("Getting details of domain transfer");

        $command = new DomainTransferInfo($domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function contactTransferQuery($contact)
    { }

    function contactTransferCancel($contact)
    { }

    function contactTransferRequest($contact, $authInfo)
    { }

    function contactTransferApprove($contact)
    { }

    function contactTransferReject($contact)
    { }

    function createDomain(
        $domain,
        $period,
        $registrant_cid,
        $contact_tech_cid,
        $contact_billing_cid,
        $contact_onsite_cid,
        $contact_reseller_cid,
        $nameservers = []
    ) {
        $this->debug("Creating domain");

        $command = new DomainCreate(
            $domain,
            $period,
            $registrant_cid,
            $contact_tech_cid,
            $contact_billing_cid,
            $contact_onsite_cid,
            $contact_reseller_cid,
            $nameservers
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    function createHost($host)
    { }

    function createContact(
        $name,
        $organization,
        $street1,
        $street2,
        $street3,
        $city,
        $state_province,
        $postal_code,
        $country_code,
        $phone,
        $fax,
        $email,
        $natural_person,
        $contact_type = 'registrant'
    ) {
        $this->debug("Creating contact");

        $command = new ContactCreate(
            $name,
            $organization,
            $street1,
            $street2,
            $street3,
            $city,
            $state_province,
            $postal_code,
            $country_code,
            $phone,
            $fax,
            $email,
            $contact_type,
            $natural_person
        );
        $frame = new Frame($command);

        return $this->request($frame);
    }

    function updateDomain($domain, $add, $rem, $chg)
    { }

    function updateDNSSEC($domain, $add = [], $rem = [])
    {
        $this->debug("updating dnssec data");
        $command = new DomainUpdateDNSSEC($domain, $add, $rem);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function updateNameservers($domain, $add, $rem)
    {
        $this->debug("Updating domain nameservers");
        $command = new DomainUpdateNS($domain, $add, $rem);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function updateContact(
        $id,
        $name,
        $organization,
        $street1,
        $street2,
        $street3,
        $city,
        $state_province,
        $postal_code,
        $country_code,
        $phone,
        $fax,
        $email,
        $natural_person
    ) {
        $this->debug("Updating contact");

        $command = new ContactUpdate(
            $id,
            $name,
            $organization,
            $street1,
            $street2,
            $street3,
            $city,
            $state_province,
            $postal_code,
            $country_code,
            $phone,
            $fax,
            $email,
            $natural_person
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    function updateHost($host, $add, $rem, $chg)
    { }

    function deleteDomain($domain, $delDate)
    {
        $this->debug("Setting deletion date for the domain");

        $command = new DomainDelete($domain, $delDate);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function undeleteDomain($domain)
    {
        $this->debug("Cancelling deletion of the domain");

        $command = new DomainUndelete($domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function deleteHost($host)
    { }

    function deleteContact($contact)
    { }

    function renewDomain($domain, $period, $curExpDate, $unit = 'y')
    {
        $this->debug("Renewing the domain");

        $command = new DomainRenew($domain, $period, $curExpDate, $unit);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function request($frame)
    {
        $this->sendFrame($frame->getXML());
        $dom          = $this->getFrame();
        $this->result = new Response($dom);
        $response     = $frame->getResult($dom);

        return $response;
    }

    function logout()
    {
        $this->debug("Logging out");

        $command = new Logout($this->user, $this->pass);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    function connect($host, $port = 700, $timeout = 1, $ssl = true, $context = NULL)
    {
        $this->debug("Attempting to connect to {$host}:{$port}");
        $dom = parent::connect($host, $port, $timeout, $ssl, $context);
        $this->debug("Connected OK");
        $this->connected = true;
    }

    function getFrame()
    {
        $xml = parent::getFrame();
        $this->xml = $xml;

        foreach (explode("\n", str_replace('><', ">" . PHP_EOL . "<", trim($xml))) as $line) {
            $this->debug("S: %s", $line);
        }

        $dom = new \DOMDocument;
        $dom->loadXML($this->xml);

        return $dom;
    }

    function sendFrame($xml)
    {
        foreach (explode("\n", str_replace('><', ">" . PHP_EOL . "<", trim($xml))) as $line) {
            $this->debug("C: %s", $line);
        }

        return parent::sendFrame($xml);
    }

    protected function debug()
    {
        if (!$this->debug) return true;
        $args = func_get_args();

        if (function_exists('logActivity')) {
            Eurid::logActivity(vsprintf(array_shift($args), $args));
        } else {
            fwrite(STDERR, vsprintf(array_shift($args), $args) . PHP_EOL);
        }
    }

    function __destruct()
    {
        if ($this->logged_in) $this->logout();

        $this->debug("disconnecting from server");
        $this->disconnect();
    }
}
