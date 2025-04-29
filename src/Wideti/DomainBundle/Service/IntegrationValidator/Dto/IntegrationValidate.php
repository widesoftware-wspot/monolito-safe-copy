<?php


namespace Wideti\DomainBundle\Service\IntegrationValidator\Dto;


class IntegrationValidate
{
    /**
     * @var bool
     */
    private $valid;

    /**
     * @var string
     */
    private $errorMessage;

    private function __construct()
    {
    }

    /**
     * @return IntegrationValidate
     */
    public static function valid()
    {
        $integrationValid = new IntegrationValidate();
        $integrationValid->valid = true;
        $integrationValid->errorMessage = null;
        return $integrationValid;
    }

    /**
     * @param $errorMessage
     * @return IntegrationValidate
     */
    public static function fail($errorMessage)
    {
        $integrationValid = new IntegrationValidate();
        $integrationValid->valid = false;
        $integrationValid->errorMessage = $errorMessage;
        return $integrationValid;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }
}