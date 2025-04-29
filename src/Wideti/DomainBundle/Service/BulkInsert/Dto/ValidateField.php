<?php

namespace Wideti\DomainBundle\Service\BulkInsert\Dto;

class ValidateField
{
    /**
     * @var boolean
     */
    private $valid;
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     */
    private $value;

    /**
     * ValidateField constructor.
     * @param bool $valid
     * @param string $message
     * @param string $value
     */
    public function __construct($valid = null, $message = null, $value = null)
    {
        $this->valid = $valid;
        $this->message = $message;
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }



}