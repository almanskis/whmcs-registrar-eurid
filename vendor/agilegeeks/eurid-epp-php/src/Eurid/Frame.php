<?php

namespace AgileGeeks\EPP\Eurid;

class Frame
{
    private $xml;
    private $frame;

    private const XML_PREFIX = '<?xml version="1.0" encoding="UTF-8"?>';
    private const TEMPLATE   = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">%s</epp>';

    public function __construct($frame)
    {
        $this->frame = $frame;

        $xml = self::XML_PREFIX.PHP_EOL . self::TEMPLATE.PHP_EOL;
        $xml = sprintf($xml, $frame->getXML());

        $this->xml = $xml;
    }

    public function getXML()
    {
        return $this->xml;
    }

    public function getResult($dom)
    {
        return $this->frame->getResult($dom);
    }
}
