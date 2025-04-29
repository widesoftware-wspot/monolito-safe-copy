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

class GetAdminVisitantesHandler implements AnalyticsHandler
{
	const EVENT_CATEGORY    = 'Visitantes';
	const EVENT_NAME        = 'Listar Visitantes';

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
	 * @throws \MongoException
	 */
	public function build(Request $request, $extra = null)
	{
		$mapper          = $this->parser->parse(file_get_contents(__DIR__ . '/../mapper.yml'));
		$mapperFields    = $mapper['routes']['admin_visitantes']['fields'];
		$filter          = $this->getFilterContent($request);
        $eventProperties = [];

		if (!$filter) return;

		foreach ($mapperFields as $key=>$event) {
			if ($this->mapperFieldExistsInFilter($filter, $key)) {
				if (!isset($filter[$key])) continue;
				$value = $this->getFieldValueByKey($filter, $key);
                $eventProperties[$event] = $value;
			}
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
	 * @param Request $request
	 * @return array|bool
	 */
	private function getFilterContent(Request $request)
	{
		$query = urldecode($request->getQueryString());
		$items = [];

		if (empty($query)) return false;

		foreach (explode('&', $query) as $chunk) {
			$param = explode("=", $chunk);

			if ($param) {
				$key = str_replace('visitantes[', '', str_replace(']', '', $param[0]));
				$items[$key] = $param[1];
			}
		}

		return $items;
	}

	/**
	 * @param array $postFields
	 * @param $key
	 * @return bool
	 */
	private function mapperFieldExistsInFilter(array $postFields, $key)
	{
		return array_key_exists($key, $postFields);
	}

	/**
	 * @param $filter
	 * @param $key
	 * @return string
	 * @throws \MongoException
	 */
	private function getFieldValueByKey($filter, $key)
	{
		return $this->transformValue($filter, $key);
	}

	/**
	 * @param $filter
	 * @param $key
	 * @return string
	 * @throws \MongoException
	 */
	private function transformValue($filter, $key)
	{
		$mongo = $this->getMongoConnection();
		$value = $filter[$key];

		if (strpos($value, 'properties') !== false) {
			$key            = explode('.', $value);
			$collection     = $mongo->selectCollection('fields');
			$customField    = $collection->findOne(['identifier' => $key[1]]);
			return $customField ? $customField['name']['pt_br'] : $key[1];
		}

		if ($key == 'group') {
			if ($value == 'all') return 'Todos';
			$collection = $mongo->selectCollection('groups');
			$group      = $collection->findOne(['shortcode' => $value]);
			return $group ? $group['name'] : $value;
		}

		return $value;
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
