<?php

namespace Wideti\DomainBundle\Service\Analytics\Handlers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder;

class GetAdminGuestsReportsHandler implements AnalyticsHandler
{
	const EVENT_CATEGORY    = 'Relatórios';
	const EVENT_NAME        = 'Visitantes';

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
	 * GetAdminVisitantesHandler constructor.
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
		$mapperFields    = $mapper['routes']['admin_guests_reports']['fields'];
		$filters         = $this->convertQueryStringToArray($request->getRequestUri());
        $eventProperties = [];

		if (empty($filters)) {
			$this->eventDispatch();
			return;
		}

		foreach ($mapperFields as $key=>$event) {
			if (!array_key_exists($key, $filters)) continue;

			if ($key == 'field') {
				$value = $this->translateFilter($filters[$key]);
			} else {
				$value = $this->translate($filters[$key]);
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
		if (strpos($requestUri, 'guestReportsFilter') === false) return [];
		$explode = explode('?', $requestUri);
		parse_str($explode[1], $params);
		return $params['guestReportsFilter'];
	}

	private function translate($item)
	{
		$items = [
			''              => 'N/I',
			'unique'        => 'Únicos',
			'returning'     => 'Recorrentes',
			'asc'           => 'Crescente',
			'desc'          => 'Decrescente',
			'created'       => 'Cadastros entre',
			'lastAccess'    => 'Visitas entre',
		];

		return $items[$item];
	}

	private function translateFilter($item)
	{
		$items = [
			''                  => 'N/I',
			'properties.name'   => 'Nome',
			'created'           => 'Data de cadastro',
			'lastAccess'        => 'Última visita'
		];

		$filter = array_merge($items, $this->getCustomFields());

		return $filter[$item];
	}

	private function getCustomFields()
	{
		$mongo          = $this->getMongoConnection();
		$collection     = $mongo->selectCollection('fields');
		$customFields   = $collection->find(['isLogin' => true]);

		$fields = [];

		foreach ($customFields as $field) {
			$fields[$field['identifier']] = $field['name']['pt_br'];
		}

		return $fields;
	}

	/**
	 * @return \MongoDB
	 */
	private function getMongoConnection()
	{
		$mongo          = $this->container->get('doctrine.odm.mongodb.document_manager');
		$conection      = $mongo->getConnection();
		$mongoClient    = $conection->getMongoClient();
		$databasename   = StringHelper::slugDomain($this->client->getDomain());
		return $mongoClient->selectDB($databasename);
	}
}
