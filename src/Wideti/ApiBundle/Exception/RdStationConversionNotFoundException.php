<?php

namespace Wideti\ApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class RdStationConversionNotFoundException
 * @package Wideti\ApiBundle\Exception
 */
class RdStationConversionNotFoundException extends \Exception
{

    const DEFAULT_MESSAGE = 'Conversao nao suportada';

    /**
     * Exception personalizada para conversão não suportada pelo sistema
     * @param int $code
     * @param string $message
     * @param \Exception $previous
     * @param array $headers
     */
    public function __construct($message = '', $code = 409, \Exception $previous = null, array $headers = array())
    {
        parent::__construct($message, $code, $previous);
        $message = self::DEFAULT_MESSAGE . ": $message";
    }

}
