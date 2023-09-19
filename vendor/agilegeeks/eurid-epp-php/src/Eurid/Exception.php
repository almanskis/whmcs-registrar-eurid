<?php

namespace AgileGeeks\EPP\Eurid;

class Exception extends \Exception
{
    private $reason = '';
    private $data   = [];
    private $action = '';

    /**
     * @param string $message
     * @param string $code
     * @param string $reason
     * @param array $data
     * @param string $action
     */
    public function __construct(string $message, string $code, $reason = '', $data = [], $action = '')
    {
        $this->reason = $reason;
        $this->data   = $data;
        $this->action = $action;

        parent::__construct($message, $code);
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }
}
