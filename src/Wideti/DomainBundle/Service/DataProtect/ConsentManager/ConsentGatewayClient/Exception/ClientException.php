<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Exception;


class ClientException extends \Exception
{
    private $statusCode;
    private $response;

    public function __construct($statusCode, $response, $message = "")
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getResponse()
    {
        return $this->response;
    }
}