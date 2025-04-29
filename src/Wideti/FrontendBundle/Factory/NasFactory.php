<?php
namespace Wideti\FrontendBundle\Factory;

use phpDocumentor\Reflection\Type;
use Symfony\Component\HttpKernel\Kernel;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\NasEmptyException;
use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\FrontendBundle\Factory\NasHandlers\NasExtraConfig;
use Wideti\FrontendBundle\Factory\NasHandlers\NasParameterHandler;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\ParameterValidatorImp;

class NasFactory
{
    /**
     * @param $nas
     * @param $params
     * @return mixed
     * @throws NasEmptyException
     * @throws NasWrongParametersException
     * @throws \ReflectionException
     */
    public static function factory($nas, $params, $customParam = null)
    {
        if (!$nas) {
            throw new NasEmptyException("Vendor name is NULL on Nas Factory.");
        }

        $handler = static::makeNasHandler($nas, $params);
        if ($handler instanceof NasExtraConfig && $customParam) {
            $handler->setExtraConfig($customParam);
        }
        return $handler->buildNas();
    }

    /**
     * @param $nas
     * @param array $param
     * @return object
     * @throws NasWrongParametersException
     * @throws \ReflectionException
     */
    protected static function makeNasHandler($nas, array $param)
    {
        $className = 'Wideti\FrontendBundle\Factory\NasHandlers\\' .
            preg_replace('/[^A-Za-z0-9]/', '', ucwords($nas)) . 'Handler';

        if (!class_exists($className)) {
            throw new NasWrongParametersException("No vendor name specified in request or not exists {$nas}");
        }

        $validator = new ParameterValidatorImp($nas, $param);
        $clazz = new \ReflectionClass($className);
        return $clazz->newInstance($param, $nas, $validator);
    }
}
