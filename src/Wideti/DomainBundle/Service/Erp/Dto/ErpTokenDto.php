<?php


namespace Wideti\DomainBundle\Service\Erp\Dto;


class ErpTokenDto
{
    private $token;
    private $errorMsg;

    private function __construct()
    {
        $this->token = null;
        $this->errorMsg = null;
    }

    /**
     * @param $token
     * @return ErpTokenDto
     */
    public static function create($token)
    {
        $tokenDto = new ErpTokenDto();
        $tokenDto->token = $token;
        return $tokenDto;
    }

    /**
     * @param $error
     * @return ErpTokenDto
     */
    public static function error($error)
    {
        $tokenDto = new ErpTokenDto();
        $tokenDto->errorMsg = $error;
        return $tokenDto;
    }

    /**
     * @return null
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return !is_null($this->errorMsg);
    }

    /**
     * @return null
     */
    public function getToken()
    {
        return $this->token;
    }
}