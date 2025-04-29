<?php

namespace Wideti\DomainBundle\Service\Analytics\AnalysisTarget;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Service\Analytics\Dto\EventDto;

class Amplitude implements Analyzer
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Amplitude constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function transform(EventDto $event)
    {
        $datetime  = new \DateTime();
        $sessionId = $datetime->getTimestamp() * 1000;

        $content = [
            "api_key" => $this->getApiKey(),
            "events" => [
                [
                    "user_id" => $event->getUserEmail(),
                    "user_properties" => [
                        "nome"      => $event->getUserName(),
                        "role"      => $event->getUserRole(),
                        "dominio"   => $event->getClientDomain(),
                        "segmento"  => $event->getClientSegment()
                    ],
                    "event_type" => $this->stringify($event->getName()),
                    "session_id" => $this->container->get('session')->get('amplitude_session_id')
                ]
            ]
        ];

        if ($event->getEventProperties()) {
            $content['events'][0]['event_properties'] = $event->getEventProperties();
        }

        $content['events'][0]['time'] = time() * 1000;

        return $content;
    }

    private function getApiKey()
    {
        return $this->container->getParameter('analytics_api_key');
    }

    private function stringify($string)
    {
        return str_replace(' ', '_', ucwords($string));
    }
}
