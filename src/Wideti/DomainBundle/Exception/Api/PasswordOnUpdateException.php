<?php

namespace Wideti\DomainBundle\Exception\Api;

class PasswordOnUpdateException extends ApiException
{
    protected $message = "Password não pode ser enviado ao atualizar uma entidade";

    public function __construct($httpStatus = 403, $message = '', $previous = null, $headers = [], $code = 500)
    {
        $this->message = empty($message) ? $this->message : $message;
        parent::__construct($httpStatus, $this->message, $previous, $headers, $code);
    }
}