<?php

namespace Wideti\DomainBundle\Gateways\Ruckus;

use Wideti\DomainBundle\Gateways\Sessions\SessionResponse;
use Exception;

class RuckusResponse
{
    /**
     * @var string
     */
    private $replyMessage;
    /**
     * @var int
     */
    private $responseCode;
    /**
     * @var string
     */
    private $decUeIp;
    /**
     * @var bool
     */
    private $hasError;
    /**
     * @var Exception
     */
    private $error;

    /**
     * @param $replyMessage
     * @param $responseCode
     * @param $decUeIp
     */
    public function __construct($replyMessage, $responseCode, $decUeIp)
    {
        $this->replyMessage = $replyMessage;
        $this->responseCode = $responseCode;
        $this->decUeIp = $decUeIp;
        $this->hasError = false;
    }

    /**
     * @param $replyMessage
     * @param $responseCode
     * @param $decUeIp
     * @return RuckusResponse
     */
    public static function create($replyMessage, $responseCode, $decUeIp) {
        return new RuckusResponse($replyMessage, $responseCode, $decUeIp);
    }

    /**
     * @return string
     */
    public function getReplyMessage()
    {
        return $this->replyMessage;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getDecUeIp()
    {
        return $this->decUeIp;
    }

    /**
     * @param $responseCode
     * @return RuckusResponse
     */
    public function withResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    /**
     * @param $replyMessage
     * @return RuckusResponse
     */
    public function withReplyMessage($replyMessage)
    {
        $this->replyMessage = $replyMessage;
        return $this;
    }

    /**
     * @param $decUeIp
     * @return RuckusResponse
     */
    public function withDecUeIp($decUeIp)
    {
        $this->decUeIp = $decUeIp;
        return $this;
    }


    /**
     * @param Exception $err
     * @return RuckusResponse
     */
    public function withError(Exception $err) {
        $this->hasError = true;
        $this->error = $err;
        return $this;
    }
}