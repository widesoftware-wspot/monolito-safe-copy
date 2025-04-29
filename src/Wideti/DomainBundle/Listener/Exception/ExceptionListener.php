<?php

namespace Wideti\DomainBundle\Listener\Exception;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Listener\Exception\Handlers\ExceptionApiHandler;
use Wideti\DomainBundle\Listener\Exception\Handlers\ExceptionWebHandler;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

/**
 * Trata somente exceptions da rota /api
 * Class ExceptionListener
 * @package Wideti\ApiBundle\Listener
 */
class ExceptionListener
{
    use TwigAware;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logger    = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $uri    = $event->getRequest()->getRequestUri();

        if ($this->container->getParameter('kernel.environment') == 'dev') {
            return;
        }

        if ($this->isApiUrl($uri)) {
            $handler = new ExceptionApiHandler($this->logger);
        } else {
            $handler = new ExceptionWebHandler($this->twig, $this->logger);
        }

        $event->setResponse($handler->getResponse($event));
    }

    /**
     * @param $uri
     * @return bool
     */
    public function isApiUrl($uri)
    {
        $exploded = explode("/", $uri);
        return in_array("api", $exploded);
    }
}
