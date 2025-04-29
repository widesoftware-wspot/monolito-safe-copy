<?php

namespace Wideti\DomainBundle\Service\Analytics\Handlers\Custom;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder;
use Wideti\DomainBundle\Service\Analytics\Handlers\AnalyticsHandler;

class PostBusinessHoursCreateHandler implements AnalyticsHandler
{
	const EVENT_CATEGORY    = 'Horário de Funcionamento';
	const EVENT_NAME        = 'Cadastrar Horario Funcionamento';

	/**
	 * @var AnalyticsService
	 */
	private $analyticsService;
	/**
	 * @var Parser
	 */
	private $parser;
	/**
	 * @var array
	 */
	private $entityData;
	/**
	 * @var ContainerInterface
	 */
	private $container;
	/**
	 * @var Client $client
	 */
	private $client;
	/**
	 * @var Users $user
	 */
	private $user;

	/**
	 * PostBusinessHoursCreateHandler constructor.
	 * @param ContainerInterface $container
	 * @param AnalyticsService $analyticsService
	 * @param Parser $parser
	 * @param $entityData
	 */
	public function __construct(
	    ContainerInterface $container,
        AnalyticsService $analyticsService,
        Parser $parser,
        $entityData
    ) {
		$this->container        = $container;
		$this->analyticsService = $analyticsService;
		$this->parser           = $parser;
		$this->entityData       = $entityData;
		$this->client           = $this->container->get('session')->get('wspotClient');
		$this->user             = $this->container->get('security.token_storage')->getToken()->getUser();
	}

	/**
	 * @param Request $request
	 * @param null $extra
	 * @return mixed|void
	 */
	public function build(Request $request, $extra = null)
	{
		$postFields = $request->request->all();

		$value = $this->getFieldValueByKey($postFields['wspot_business_hours'], 'inAccessPoints');
		$this->eventDispatch(['Pontos de Acesso' => $value]);
	}

    /**
     * @param array|null $eventProperties
     * @return mixed|void
     */
	public function eventDispatch(array $eventProperties = null)
	{
		$builder = new EventBuilder();
		$event = $builder
			->withClientDomain($this->client->getDomain())
			->withClientSegment($this->client->getSegment() ? $this->client->getSegment()->getName() : 'N/I')
			->withUserName($this->user->getNome())
			->withUserEmail($this->user->getUsername())
			->withUserRole($this->user->getRole()->getName())
            ->withCategory(self::EVENT_CATEGORY)
			->withName(self::EVENT_NAME)
			->withEventProperties($eventProperties)
            ->withSessionId(null)
			->build();

		$this->analyticsService->sendEvent($event);
	}

	private function getFieldValueByKey($fields, $key)
	{
		return ($fields[$key] == '1') ? 'Escolher ponto de acesso' : 'Todos';
	}
}
