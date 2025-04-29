<?php

namespace Wideti\DomainBundle\Validator;

class EmailValidate
{
    private $email;

    private function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function validate($email)
    {
        $regex = "[áàâãäªéèêëíìîïóòôõöºúùûüçñ ]+";
        return (bool) preg_match("/" . $regex . "/i", $email);
    }
}
