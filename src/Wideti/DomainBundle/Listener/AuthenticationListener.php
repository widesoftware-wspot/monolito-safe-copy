<?php
namespace Wideti\DomainBundle\Listener;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Event\AuthenticationEvent;
use Wideti\DomainBundle\Exception\ClientNotExistInSessionException;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AuthenticationListener
{
    use ElasticSearchAware;
    use SessionAware;

    public function onAuthentication(AuthenticationEvent $event)
    {
        $guest  = $event->getGuest();
        $method = $event->getMethod();

        $name = '';

        if (array_key_exists('name', $guest->getProperties())) {
            $name = $guest->getProperties()['name'];
        }

        /**
         * @var Client $client
         */
        $client = $this->getLoggedClient();

        if (empty($client)) {
            throw new ClientNotExistInSessionException("Cliente não encontrado na sessão no AuthenticationListener");
        }

        $document = [
            'client_id' => $client->getId(),
            'method'    => $method,
            'guest'     => [
                'id'   => $guest->getMysql(),
                'name' => $name
            ],
            'date'      => date('Y-m-d H:i:s')
        ];

        $this->elasticSearchService->post('accountings', 'login', $document);
    }
}
