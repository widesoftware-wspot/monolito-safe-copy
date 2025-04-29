<?php

namespace Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto;

class ResponseDto
{
    public $statusCode;
    public $message;

    /**
     * ResponseDto constructor.
     * @param $statusCode
     * @param $message
     */
    public function __construct($statusCode, $message)
    {
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
}
