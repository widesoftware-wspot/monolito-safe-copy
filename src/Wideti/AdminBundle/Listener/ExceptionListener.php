<?php
namespace Wideti\AdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Wideti\DomainBundle\Exception\NotAuthorizedPlanException;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\AdminBundle\Exception\ObjectNotFoundException;

/**
 * Custom exception listener.
 */
class ExceptionListener
{
    use TwigAware;

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ObjectNotFoundException) {
            $template = $this->render(
                'AdminBundle:Admin:notFound.html.twig',
                [
                    'message' => $exception->getMessage()
                ]
            );

            $event->setResponse($template);
        }

        if($exception instanceof NotAuthorizedPlanException) {
            $template = $this->render(
                'AdminBundle:Admin:planPermission.html.twig'
            );

            $event->setResponse($template);
        }
    }
}
