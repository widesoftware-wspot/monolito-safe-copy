<?php
namespace Wideti\DomainBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientNotFoundException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
