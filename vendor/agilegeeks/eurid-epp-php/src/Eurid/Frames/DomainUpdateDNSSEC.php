<?php
namespace AgileGeeks\EPP\Eurid\Frames;

use AgileGeeks\EPP\Eurid\Frames\Command;

require_once(__DIR__.'/Command.php');

class DomainUpdateDNSSEC extends Command
{
    const TEMPLATE = <<<XML
    <command>
        <update>
            <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                <domain:name>%s</domain:name>
            </domain:update>
        </update>
        <extension>
            <secDNS:update xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1">
                %s
            </secDNS:update>
        </extension>
    <clTRID>%s</clTRID>
    </command>
XML;

    function __construct($domain, $add = [], $rem = [])
    {
        $_str = '';

        if (count($add) > 0) {
            $_str = "<secDNS:add>".PHP_EOL;

            foreach ($add as $a) {
                $_str .= "    <secDNS:keyData>".PHP_EOL;
                $_str .= "        <secDNS:flags>{$a->flags}</secDNS:flags>".PHP_EOL;
                $_str .= "        <secDNS:protocol>{$a->protocol}</secDNS:protocol>".PHP_EOL;
                $_str .= "        <secDNS:alg>{$a->alg}</secDNS:alg>".PHP_EOL;
                $_str .= "        <secDNS:pubKey>{$a->pubKey}</secDNS:pubKey>".PHP_EOL;
                $_str .= "    </secDNS:keyData>".PHP_EOL;
            }

            $_str .= "</secDNS:add>".PHP_EOL;
        } else if (count($rem) > 0) {
            $_str = "<secDNS:rem>".PHP_EOL;

            foreach ($rem as $r) {
                $_str .= "    <secDNS:keyData>".PHP_EOL;
                $_str .= "        <secDNS:flags>{$r->flags}</secDNS:flags>".PHP_EOL;
                $_str .= "        <secDNS:protocol>{$r->protocol}</secDNS:protocol>".PHP_EOL;
                $_str .= "        <secDNS:alg>{$r->alg}</secDNS:alg>".PHP_EOL;
                $_str .= "        <secDNS:pubKey>{$r->pubKey}</secDNS:pubKey>".PHP_EOL;
                $_str .= "    </secDNS:keyData>".PHP_EOL;
            }

            $_str .= "</secDNS:rem>".PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            $domain,
            $_str,
            $this->clTRID()
        );
    }

    function getResult($dom)
    {
        parent::getResult($dom);
        
        return (object)[];
    }
}
