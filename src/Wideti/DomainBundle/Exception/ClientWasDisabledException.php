<?php
namespace Wideti\DomainBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientWasDisabledException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(401, $message, $previous, array(), $code);
    }
}
