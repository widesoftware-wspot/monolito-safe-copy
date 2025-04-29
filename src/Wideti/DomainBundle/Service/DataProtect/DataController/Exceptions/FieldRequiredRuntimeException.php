<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions;


class FieldRequiredRuntimeException extends FieldValidationRuntimeException
{

    /**
     * FieldRequiredRuntimeException constructor.
     * @param $fieldRequired
     */
    public function __construct($message, $fieldRequired)
    {
        parent::__construct($message, $fieldRequired);
    }

    /**
     * @return mixed
     */
    public function getFieldRequired()
    {
        return $this->getField();
    }
}