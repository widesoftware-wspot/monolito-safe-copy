<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions;


class InvalidFormatRuntimeException extends FieldValidationRuntimeException
{
    private $valueReceived;
    /**
     * FieldRequiredRuntimeException constructor.
     * @param $valueReceived
     */
    public function __construct($message, $field, $valueReceived)
    {
        parent::__construct($message, $field);
        $this->valueReceived = $valueReceived;
    }

    /**
     * @return mixed
     */
    public function getValueReceived()
    {
        return $this->valueReceived;
    }
}