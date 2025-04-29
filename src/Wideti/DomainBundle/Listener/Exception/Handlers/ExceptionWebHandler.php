<?php

namespace Wideti\DomainBundle\Listener\Exception\Handlers;

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\DomainBundle\Exception\ClientWasDisabledException;
use Wideti\DomainBundle\Exception\CookieDisabledException;

class ExceptionWebHandler implements Handler
{

    /**
     * @var TimedTwigEngine
     */
    private $twig;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(TwigEngine $twig, Logger $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function getResponse(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ClientWasDisabledException) {
            $this->logger->addInfo($exception->getMessage(), ['context' => $event]);
            return $this->twig->renderResponse('@Twig/Exception/error401.html.twig', ['status_code' => 401]);
        }

        if ($exception instanceof AccessDeniedHttpException) {
            $this->logger->addInfo($exception->getMessage(), ['context' => $event]);
            return $this->twig->renderResponse('@Twig/Exception/error403.html.twig', ['status_code' => 403]);
        }

        if ($exception instanceof NotFoundHttpException) {
            $this->logger->addInfo($exception->getMessage(), ['context' => $event]);
            return $this->twig->renderResponse('@Twig/Exception/error404.html.twig', ['status_code' => 404]);
        }

        if ($exception instanceof CookieDisabledException) {
            return $this->twig->renderResponse('@Twig/Exception/errorCookieDisabled.html.twig', ['status_code' => 400]);
        }

        $this->logger->addCritical($exception->getMessage(), ['context' => $event]);
        return $this->twig->renderResponse('@Twig/Exception/error500.html.twig', ['status_code' => 500]);
    }
}
