<?php

namespace Wideti\DomainBundle\Service\ClientLogs\Dto;

class ClientOptionsGetLogDto
{
    private $clientId;

    private $page;

    private $size;

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function setPage($page)
    {
        if ($page < 0) {
            $page = 0;
        }
        $this->page = $page;
    }
}