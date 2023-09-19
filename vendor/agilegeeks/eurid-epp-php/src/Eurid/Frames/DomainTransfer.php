<?php

namespace AgileGeeks\EPP\Eurid\Frames;

use AgileGeeks\EPP\Eurid\Frames\Command;

require_once(__DIR__ . '/Command.php');

class DomainTransfer extends Command
{
    const TEMPLATE = <<<XML
    <command>
        <transfer op="request">
          <domain:transfer xmlns:domain='urn:ietf:params:xml:ns:domain-1.0'>
            <domain:name>%s</domain:name>
            %s
            <domain:authInfo>
              <domain:pw>%s</domain:pw>
            </domain:authInfo>
          </domain:transfer>
        </transfer>
        <extension>
            <domain-ext:transfer xmlns:domain='urn:ietf:params:xml:ns:domain-1.0' xmlns:domain-ext='http://www.eurid.eu/xml/epp/domain-ext-2.3'>
                <domain-ext:request>
                    <domain-ext:registrant>%s</domain-ext:registrant>
                    <domain-ext:contact type='billing'>%s</domain-ext:contact>
                    <domain-ext:contact type='tech'>%s</domain-ext:contact>
                    %s
                </domain-ext:request>
            </domain-ext:transfer>
        </extension>
        <clTRID>%s</clTRID>
    </command>
XML;

    function __construct($domain, $pw, $period, $cid, $billing, $tech, $nameservers, $unit = 'y')
    {
        $period_template = '';
        if ($period !== 0) {
            $period_template = "<domain:period unit='{$unit}'>{$period}</domain:period>".PHP_EOL;
        }

        $nameservers_template = '';
        if (sizeof($nameservers) > 0) {
            $nameservers_template = '<domain-ext:ns>'.PHP_EOL;

            foreach ($nameservers as $ns) {
                $ns_ip = '';
                if (empty($ns[1]) === false){
                    $ns_ip .= "<domain:hostAddr>{$ns[1]}</domain:hostAddr>".PHP_EOL;
                }

                $nameservers_template .= "<domain:hostAttr>".PHP_EOL;
                $nameservers_template .= "    <domain:hostName>{$ns[0]}</domain:hostName>".PHP_EOL;
                $nameservers_template .= "    {$ns_ip}";
                $nameservers_template .= "</domain:hostAttr>".PHP_EOL;
            }

            $nameservers_template .= '</domain-ext:ns>'.PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            $domain,
            $period_template,
            $pw,
            $cid,
            $billing,
            $tech,
            $nameservers_template,
            $this->clTRID()
        );
    }

    function getResult($dom)
    {
        parent::getResult($dom);

        $result = new \stdClass();
        $trnData_node     = $dom->getElementsByTagName('trnData')->item(0);
        $result->trStatus = $trnData_node->getElementsByTagName('trStatus')->item(0)->firstChild->textContent;
        $result->exDate   = $trnData_node->getElementsByTagName('exDate')->item(0)->firstChild->textContent;

        return $result;
    }
}
