<?php

namespace Wideti\DomainBundle\Service\Analytics\Handlers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder;

class GetAdminExportMostVisitedHoursHandler implements AnalyticsHandler
{
    const EVENT_CATEGORY    = 'Relatórios';
    const EVENT_NAME        = 'Horarios Mais Visitados';

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
     * GetAdminCampaignCtaReportHandler constructor.
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
        $fileFormat = $this->convertQueryStringToArray($request->getRequestUri());
        $this->eventDispatch(['Exportar' => $fileFormat]);
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
        if (strpos($requestUri, 'filters') === false) return [];
        $explode = explode('?', $requestUri);
        parse_str($explode[1], $params);

        $fileFormat = array_key_exists('fileFormat', $params)
            ? strtoupper($params['fileFormat'])
            : 'N/I';

        if ($fileFormat == 'XLSX') return 'EXCEL';
        return $fileFormat;
    }
}
