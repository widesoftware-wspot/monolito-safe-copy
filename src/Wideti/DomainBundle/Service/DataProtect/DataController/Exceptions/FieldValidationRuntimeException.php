<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions;


class FieldValidationRuntimeException extends \RuntimeException
{
    private $field;

    /**
     * FieldValidationRuntimeException constructor.
     * @param $field
     */
    public function __construct($message, $field)
    {
        parent::__construct($message);
        $this->field = $field;
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }
}