<?php

namespace Wideti\DomainBundle\Service\Analytics\Handlers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder;

class GetAdminCampaignReportHandler implements AnalyticsHandler
{
	const EVENT_CATEGORY    = 'Relatórios';
	const EVENT_NAME        = 'Campanhas Visualizacoes';

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
	 * GetAdminCampaignReportHandler constructor.
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
		$mapper          = $this->parser->parse(file_get_contents(__DIR__ . '/../mapper.yml'));
		$mapperFields    = $mapper['routes']['admin_campaign_report']['fields'];
		$filters         = $this->convertQueryStringToArray($request->getRequestUri());
        $eventProperties = [];

		if (empty($filters)) {
			$this->eventDispatch();
			return;
		}

		$filter = [
			'campaign',
			'access_point'
		];

		foreach ($mapperFields as $item) {
			if (!array_key_exists($item, $filters)) continue;

			if (in_array($item, $filter)) {
				$event = 'Filtrar';
				$value = $this->translate($item);
			} else {
				$event = 'Período';
				$value = $this->getPeriod($filters);
				if (!$value) continue;
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

	private function convertQueryStringToArray($requestUri)
	{
		if (strpos($requestUri, 'campaignReportFilter') === false) return [];
		$explode = explode('?', $requestUri);
		parse_str($explode[1], $params);
		return $params['campaignReportFilter'];
	}

	private function translate($item)
	{
		$items = [
			'campaign'      => 'Campanha',
			'access_point'  => 'Ponto de Acesso'
		];

		return $items[$item];
	}

	private function getPeriod(array $filters)
	{
		$dateFrom   = array_key_exists('date_from', $filters) ? $filters['date_from'] : null;
		$dateTo     = array_key_exists('date_to', $filters) ? $filters['date_to'] : null;

		if (!$dateFrom && !$dateTo) return null;

		return "De: {$dateFrom} Até: {$dateTo}";
	}
}
