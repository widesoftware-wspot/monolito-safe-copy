<?php

namespace Wideti\DomainBundle\Service\Analytics\Handlers\Custom;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder;
use Wideti\DomainBundle\Service\Analytics\Handlers\AnalyticsHandler;

class PostGroupEditHandler implements AnalyticsHandler
{
	const EVENT_CATEGORY    = 'Grupo de Visitantes';
	const EVENT_NAME        = 'Editar Grupo Visitantes';

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
	 * PostGroupEditHandler constructor.
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
		$mapper             = $this->parser->parse(file_get_contents(__DIR__ . '/../../mapper.yml'));
		$mapperFields       = $mapper['routes']['group_edit']['fields'];
        $eventProperties    = [];

		$prePersist     = $this->getPrePersistData($this->entityData);
		$posPersist     = $request->request->all();

		foreach ($mapperFields as $key=>$event) {
			$changes = $this->checkIfHasChanges($prePersist, $posPersist, $key);
			if (!$changes) continue;
            $eventProperties[$changes['event']] = $changes['value'];
        }
        $this->eventDispatch($eventProperties);
    }

    /**
     * @param array $eventProperties
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
	 * @param $entityData
	 * @return array
	 */
	private function getPrePersistData($entityData)
	{
		/**
		 * @var Group $entity
		 */
		$entity     = $entityData['entity'];
		$prePersist = [];

		/**
		 * @var Configuration $config
		 */
		foreach ($entity->getConfigurations() as $config) {
			$shortcode = "enable_{$config->getShortcode()}";
			$prePersist[$shortcode] = $config->getConfigurationValueByKey($shortcode)->getValue();
		}

		$prePersist['apsAndGroups'] = $entity->getInAccessPoints();

		return $prePersist;
	}

	/**
	 * @param array $prePersist
	 * @param array $posPersist
	 * @param $key
	 * @return array|bool
	 */
	private function checkIfHasChanges(array $prePersist, array $posPersist, $key)
	{
		if ($key === 'apsAndGroups') {
			$oldValue = ($prePersist[$key]) ? 'Automático' : 'Manual';
			$newValue = (isset($posPersist['wideti_AdminBundle_guest_group'][$key]) && $this->arrayIsEmpty($posPersist['wideti_AdminBundle_guest_group'][$key])) ? 'Manual' : 'Automático';
			if ($oldValue === $newValue) return false;
		} else {
			$oldValue = ($prePersist[$key] === '1') ? 'Ativo' : 'Inativo';
			$newValue = (isset($posPersist['wspot_group_form'][$key]) && $posPersist['wspot_group_form'][$key] === '1') ? 'Ativo' : 'Inativo';
			if ($oldValue === $newValue) return false;
		}

		return [
			'event' => $key,
			'value' => $newValue
		];
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private function arrayIsEmpty($value)
	{
		return empty(json_decode($value, true));
	}
}
