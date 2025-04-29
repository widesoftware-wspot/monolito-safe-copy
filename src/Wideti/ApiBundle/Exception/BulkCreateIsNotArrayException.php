<?php

namespace Wideti\ApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BulkCreateIsNotArrayException extends HttpException
{
    const DEFAULT_MESSAGE = 'Você não enviou um array para operação de bulk';

    /**
     * Exception personalizada para conflito em domínio
     * @param int $code
     * @param string $message
     * @param \Exception $previous
     * @param array $headers
     */
    public function __construct(
        $code = 400,
        $message = self::DEFAULT_MESSAGE,
        \Exception $previous = null,
        array $headers = []
    ) {
        parent::__construct($code, $message, $previous, $headers, $code);
    }
}
