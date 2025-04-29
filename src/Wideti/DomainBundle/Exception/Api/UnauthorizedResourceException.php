<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 14/12/16
 * Time: 10:35
 */

namespace Wideti\DomainBundle\Exception\Api;


class UnauthorizedResourceException extends ApiException
{
    protected $message = "Você não tem permissão para acessar este recurso";

    public function __construct($httpStatus = 403, $message = '', $previous = null, $headers = [], $code = 500)
    {
        $this->message = empty($message) ? $this->message : $message;
        parent::__construct($httpStatus, $this->message, $previous, $headers, $code);
    }

}