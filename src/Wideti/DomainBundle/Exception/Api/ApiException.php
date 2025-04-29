<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 14/12/16
 * Time: 10:25
 */

namespace Wideti\DomainBundle\Exception\Api;


use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    protected $message = "Ocorreu um erro na API";

    public function __construct($httpCode = 400, $message = "", $previous = null, $headers = [], $code = 400)
    {
        $this->message = empty($message) ? $this->message : $message;

        parent::__construct($httpCode, $this->message, $previous, $headers, $code);
    }

}