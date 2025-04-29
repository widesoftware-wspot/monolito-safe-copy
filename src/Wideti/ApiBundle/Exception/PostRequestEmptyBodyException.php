<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 28/12/16
 * Time: 11:23
 */

namespace Wideti\ApiBundle\Exception;


use Symfony\Component\HttpKernel\Exception\HttpException;

class PostRequestEmptyBodyException extends HttpException
{

    const DEFAULT_MESSAGE = 'A requisição POST não pode ser enviada com o body vazio';

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