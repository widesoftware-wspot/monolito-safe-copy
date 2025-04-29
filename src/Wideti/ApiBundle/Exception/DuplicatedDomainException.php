<?php

namespace Wideti\ApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ApiException
 * @package Wideti\ApiBundle\Exception
 */
class DuplicatedDomainException extends HttpException
{
    const DEFAULT_MESSAGE = 'Houve um conflito na requisicao. Dominio ja existe em nossa base de dados, entre em contato com o suporte.';

    /**
     * Exception personalizada para conflito em domínio
     * @param int $code
     * @param string $message
     * @param \Exception $previous
     * @param array $headers
     */
    public function __construct($code = 409, $message = self::DEFAULT_MESSAGE, \Exception $previous = null, array $headers = array())
    {
        parent::__construct($code, $message, $previous, $headers, $code);
    }
}
