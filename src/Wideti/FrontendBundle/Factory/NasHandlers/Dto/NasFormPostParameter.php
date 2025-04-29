<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\Dto;

class NasFormPostParameter
{
    private $protocol;
    private $ip;
    private $port;
    private $directory;

    public function __construct($protocol, $ip, $port, $directory)
    {
        $this->protocol  = $protocol?: "";
        $this->ip        = $ip ?: "";
        $this->port      = $port ?: "";
        $this->directory = $directory ?: "";
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getPostFormUrl()
    {
        $port = $this->port ? ":{$this->port}" : "";
        $nasAddress     = '' . $this->ip . $port . $this->directory;

        if (substr($nasAddress, 0, 4) != 'http') {
            $nasAddress = $this->protocol . "://" . $nasAddress;
        }
        if ($this->protocol == '' && $this->port == '' && $this->ip == '') {
            return '';
        }
        return $nasAddress;
    }
}
