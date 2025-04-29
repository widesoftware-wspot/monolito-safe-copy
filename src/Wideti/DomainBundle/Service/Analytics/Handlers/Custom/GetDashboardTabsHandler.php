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

class GetDashboardTabsHandler implements AnalyticsHandler
{
	const EVENT_CATEGORY    = 'Dashboard';
	const EVENT_NAME        = 'Visualizar Dashboard';
	const GUESTS_TAB        = 'Visitantes';
	const NETWORK_TAB       = 'Rede';

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
	 * GetDashboardTabsHandler constructor.
	 * @param ContainerInterface $container
	 * @param AnalyticsService $analyticsService
	 * @param Parser $parser
	 * @param $entityData
	 */
	public function __construct(ContainerInterface $container, AnalyticsService $analyticsService, Parser $parser, $entityData)
	{
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
		if (!$extra) return;
		$mapper          = $this->parser->parse(file_get_contents(__DIR__ . '/../../mapper.yml'));
		$mapperFields    = $mapper['routes']['admin_dashboard']['fields'];
		$postFields      = $extra;
        $eventProperties = [];

		foreach ($mapperFields as $key=>$event) {
			if ($key == 'tab') {
				$value = $postFields[$key];
			}

			if ($key == 'filter') {
				$value = $this->getFilterValue($postFields);
				if (!$value) continue;
			}

			if ($key == 'refresh') {
				$value = ($postFields[$key] == 'true') ? 'Ativado' : 'Desativado';
			}

            $eventProperties[$event] = $value;
        }
        $this->eventDispatch($eventProperties);
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

	/**
	 * @param $postFields
	 * @return string
	 */
	private function getFilterValue($postFields)
	{
		if (empty($postFields)) return null;

		$filter = $postFields['filter'];
		$value = 'Todo período';

		if ($filter === 'last30days') {
			$value = 'Últimos 30 dias';
		}

		if ($filter === 'custom') {
			$value = 'Customizado data início e fim';
		}
		return $value;
	}
}
