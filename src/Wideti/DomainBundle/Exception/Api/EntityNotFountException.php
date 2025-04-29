<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 14/12/16
 * Time: 15:34
 */

namespace Wideti\DomainBundle\Exception\Api;


class EntityNotFountException extends ApiException
{
    protected $message = "Entidade não encontrada";

    public function __construct($httpStatus = 404, $message = '', $previous = null, $headers = [], $code = 400)
    {
        $this->message = empty($message) ? $this->message : $message;
        parent::__construct($httpStatus, $this->message, $previous, $headers, $code);
    }
}