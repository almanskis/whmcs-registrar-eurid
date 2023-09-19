<?php
namespace AgileGeeks\EPP\Eurid\Frames;

use AgileGeeks\EPP\Eurid\Frames\Command;

require_once(__DIR__.'/Command.php');

class DomainUpdateNS extends Command
{
    const TEMPLATE = <<<XML
    <command>
        <update>
            <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                <domain:name>%s</domain:name>
                %s
                %s
            </domain:update>
        </update>
        <clTRID>%s</clTRID>
    </command>
XML;

    function __construct($domain, $add = [], $rem = [])
    {
        $_add = '';
        $_rem = '';

        if (!empty($add)) {
            $_add  = "<domain:add>".PHP_EOL;
            $_add .= "    <domain:ns>".PHP_EOL;
            
            foreach ($add as $k => $a) {
                $_add .= "        <domain:hostAttr>".PHP_EOL;
                $_add .= "            <domain:hostName>{$k}</domain:hostName>".PHP_EOL;

                if (!empty($a['ips'])) {
                    foreach ($a['ips'] as $ip) {
                        $ip_version = $this->detect_ip_version($ip);
                        $_add .= "            <domain:hostAddr ip=\"{$ip_version}\">{$ip}</domain:hostAddr>".PHP_EOL;
                    }
                }

                $_add .= "        </domain:hostAttr>".PHP_EOL;
            }

            $_add .= "    </domain:ns>".PHP_EOL;
            $_add .= "</domain:add>".PHP_EOL;
        }

        if (!empty($rem)) {
            $_rem  = "<domain:rem>".PHP_EOL;
            $_rem .= "    <domain:ns>".PHP_EOL;
            foreach ($rem as $k => $r) {
                $_rem .= "        <domain:hostAttr>".PHP_EOL;
                $_rem .= "            <domain:hostName>{$k}</domain:hostName>".PHP_EOL;

                if (!empty($r['ips'])) {
                    $ip_version = $this->detect_ip_version($ip);
                    foreach ($r['ips'] as $ip) {
                        $_rem.= "            <domain:hostAddr ip=\"{$ip_version}\">{$ip}</domain:hostAddr>".PHP_EOL;
                    }
                }

                $_rem .= "        </domain:hostAttr>".PHP_EOL;
            }

            $_rem .= "    </domain:ns>".PHP_EOL;
            $_rem .= "</domain:rem>".PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            $domain,
            $_add,
            $_rem,
            $this->clTRID()
        );
    }

    private function detect_ip_version($ip)
    {
        if (strpos($ip, '.') > 0) {
            return 'v4';
        } else if (strpos($ip, ':') > 0) {
            return 'v6';
        } else {
            return false;
        }
    }

    function getResult($dom)
    {
        parent::getResult($dom);
        
        return (object)[];
    }

}
