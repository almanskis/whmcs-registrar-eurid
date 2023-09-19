<?php

namespace AgileGeeks\EPP\Eurid\Frames;

use AgileGeeks\EPP\Eurid\Frames\Command;

require_once(__DIR__ . '/Command.php');

class DomainTransferInfo extends Command
{
    const TEMPLATE = <<<XML
    <command>
        <transfer op="query">
            <domain:transfer xmlns:domain='urn:ietf:params:xml:ns:domain-1.0'>
                <domain:name>%s</domain:name>
            </domain:transfer>
        </transfer>
        <clTRID>%s</clTRID>
    </command>
XML;

    function __construct($domain)
    {
        $this->xml = sprintf(
            self::TEMPLATE,
            $domain,
            $this->clTRID()
        );
    }

    function getResult($dom)
    {
        parent::getResult($dom);

        $result = new \stdClass();
        $trnData_node        = $dom->getElementsByTagName('trnData')->item(0);
        $result->onHold      = $trnData_node->getElementsByTagName('onHold')->item(0)->firstChild->textContent;
        $result->quarantined = $trnData_node->getElementsByTagName('quarantined')->item(0)->firstChild->textContent;
        $result->suspended   = $trnData_node->getElementsByTagName('suspended')->item(0)->firstChild->textContent;
        $result->delayed     = $trnData_node->getElementsByTagName('delayed')->item(0)->firstChild->textContent;
        $result->reason      = $trnData_node->getElementsByTagName('reason')->item(0)->firstChild->textContent;

        return $result;
    }
}
