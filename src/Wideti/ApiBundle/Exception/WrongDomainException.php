<?php

namespace Wideti\ApiBundle\Exception;

class WrongDomainException extends \InvalidArgumentException
{
    const DEFAULT_MESSAGE = 'Domínio Mambo WiFi vazio ou inválido.';

    /**
     * @param int $code
     * @param string $message
     * @param \Exception $previous
     */
    public function __construct($code = 409, $message = '', \Exception $previous = null)
    {
        $message = $message ?: self::DEFAULT_MESSAGE;

        parent::__construct($message, $code, $previous);
    }
}
