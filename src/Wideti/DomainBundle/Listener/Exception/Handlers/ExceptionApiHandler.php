<?php

namespace Wideti\DomainBundle\Listener\Exception\Handlers;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\DomainBundle\Exception\Api\ApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionApiHandler implements Handler
{
    const LOGLEVEL_CRITICAL = 'critical';
    const LOGLEVEL_INFO = 'info';
    const LOGLEVEL_WARNING = 'warning';

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getResponse(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ApiException) {
            $logLv = $this::LOGLEVEL_INFO;
            if ($exception->getStatusCode() == 500) {
                $logLv = $this::LOGLEVEL_CRITICAL;
            }
            $this->sendLog($exception, $event, $logLv);
            return $this->throwApiException($event);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->throwHttpException($event, "A URL solicitada não existe.", $exception->getStatusCode());
        }

        if ($exception instanceof HttpException) {
            $logLv = $this::LOGLEVEL_INFO;
            if ($exception->getStatusCode() == 500) {
                $logLv = $this::LOGLEVEL_CRITICAL;
            }
            $this->sendLog($exception, $event, $logLv);
            return $this->throwHttpException($event, null, $exception->getStatusCode());
        }

        if ($exception instanceof ServiceNotFoundException) {
            $this->sendLog($exception, $event, $this::LOGLEVEL_CRITICAL);
            return $this->throwHttpException($event, "A URL solicitada não existe.", 404);
        }


        $this->sendLog($exception, $event);
        return $this->throwException($event);
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @param null $message
     * @param null $statusCode
     * @return Response
     */
    private function throwHttpException(GetResponseForExceptionEvent $event, $message = null, $statusCode = null)
    {
        $exception  = $event->getException();
        $response   = new Response();
        $statusCode = $statusCode ?: $exception->getCode();
        $message    = $message ?: $exception->getMessage();
        $content    = json_encode(
            [
                'message'       => $message,
                'status_code'   => $statusCode
            ]
        );

        $response->setStatusCode($statusCode);
        $response->headers->set('Content-Type', "application/json; charset=utf-8");
        $response->setCharset('utf-8');
        $response->setContent($content);

        return $response;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @return Response
     */
    private function throwApiException(GetResponseForExceptionEvent $event)
    {
        $exception  = $event->getException();
        $response   = new Response();

        $content = [
            'message'       => $exception->getMessage(),
            'status_code'   => $exception->getStatusCode()
        ];
        $content = json_encode($content);

        $response->setStatusCode($exception->getStatusCode());
        $response->headers->set('Content-Type', "application/problem+json; charset=utf-8");
        $response->setContent($content);

        return $response;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @return Response
     */
    private function throwException(GetResponseForExceptionEvent $event)
    {
        $exception  = $event->getException();
        $response   = new Response();

        $content = [
            'message'       => $exception->getMessage(),
            'status_code'   => 500
        ];
        $content    = json_encode($content);

        $response->setStatusCode(500);
        $response->headers->set('Content-Type', "application/problem+json; charset=utf-8");
        $response->setContent($content);

        return $response;
    }

    private function sendLog($exception, $event, $level = 'critical')
    {
        $info = [
            "exception_name"        => get_class($exception),
            "message"               => $exception->getMessage(),
            "original_data"         => $event->getRequest()->getContent()
        ];

        if ($level == 'critical') {
            $this->logger->addCritical("WSPOT_API", $info);
        }

        if ($level == 'info') {
            $this->logger->addInfo("WSPOT_API", $info);
        }

        if ($level == 'warning') {
            $this->logger->addWarning("WSPOT_API", $info);
        }
    }
}
