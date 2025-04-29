<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Data;

class CreateSuccess
{
    private $message;

    private function __construct()
    {
    }

    public static function create($message)
    {
        $obj = new CreateSuccess();
        $obj->message = $message;
        return $obj;
    }

    public function getMessage()
    {
        return $this->message;
    }
}