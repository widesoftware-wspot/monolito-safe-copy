<?php

namespace Wideti\DomainBundle\Service\Mailer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Exception\SendEmailFailException;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessage;
use Wideti\DomainBundle\Service\Mailer\Providers\Provider;
use Wideti\DomainBundle\Service\Mailer\Providers\SesProvider;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class MailerService
{
    use LoggerAware;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Provider
     */
    private $provider;

    private $devDeliveryAddress;

    public function __construct($parameterProvider, $devDeliveryAddress, ContainerInterface $container)
    {
        $this->container            = $container;
        $providerService            = 'mail.provider.' . $parameterProvider;
        $this->provider             = $container->get($providerService);
        $this->devDeliveryAddress   = $devDeliveryAddress;
    }

    /**
     * @param MailMessage $message
     * @return MailReturnStatus
     * @throws SendEmailFailException
     */
    public function send(MailMessage $message)
    {
        if ($this->container->getParameter('kernel.environment') == 'dev') {
            $message->setTo([
                 ['Developer' => $this->devDeliveryAddress]
            ]);
        }

        try {
            return $this->provider->send($message);
        } catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage());
            $this->logger->addCritical($e->getTraceAsString());
            if (strpos($e->getMessage(), 'Undefined index') == false) {
                throw new SendEmailFailException($e->getMessage());
            }
        }
    }


    /**
     * @param $emailAddress
     * @throws \Aws\Exception\AwsException
     */
    public function validateSender($emailAddress)
    {
        if ($this->provider instanceof SesProvider) {
            try {
                $this->provider->validateSender($emailAddress);
            } catch (\Aws\Exception\AwsException $e) {
                $this->logger->addCritical($e->getMessage());
                $this->logger->addCritical($e->getTraceAsString());
                if (strpos($e->getMessage(), 'Undefined index') == false) {
                    throw new \Aws\Exception\AwsException($e->getMessage());
                }
            }
        }
    }
}
