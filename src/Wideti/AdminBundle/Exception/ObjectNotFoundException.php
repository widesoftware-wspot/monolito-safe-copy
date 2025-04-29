<?php

namespace Wideti\AdminBundle\Exception;

class ObjectNotFoundException extends \Exception implements WspotExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $message The internal exception message
     */
    public function __construct($message = null)
    {
        parent::__construct($message);
    }
}