<?php
namespace Wideti\DomainBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientNotExistInSessionException extends HttpException
{
    private $message = "O cliente não foi encontrado na sessão";

    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        if (!empty($message)) {
            $this->message = $message;
        }

        parent::__construct(404, $this->message, $previous, [], $code);
    }
}
