<?php

namespace AgileGeeks\EPP\Eurid\Frames;

use AgileGeeks\EPP\Eurid\Frames\Command;

require_once(__DIR__ . '/Command.php');

class DomainUndelete extends Command
{
    const TEMPLATE = <<<XML
    <command>
        <delete>
            <domain:delete xmlns:domain='urn:ietf:params:xml:ns:domain-1.0'>
                <domain:name>%s</domain:name>
            </domain:delete>
        </delete>
        <extension>
            <domain-ext:delete xmlns:domain-ext='http://www.eurid.eu/xml/epp/domain-ext-2.3'>
                <domain-ext:cancel/>
            </domain-ext:delete>
        </extension>
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
        return (object)[];
    }
}
