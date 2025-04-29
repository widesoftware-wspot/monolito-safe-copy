<?php

namespace Wideti\DomainBundle\Service\Analytics\Handlers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;

interface AnalyticsHandler
{
	/**
	 * AnalyticsHandler constructor.
	 * @param ContainerInterface $container
	 * @param AnalyticsService $analyticsService
	 * @param Parser $parser
	 * @param $entityData
	 */
	public function __construct(ContainerInterface $container, AnalyticsService $analyticsService, Parser $parser, $entityData);

	/**
	 * @param Request $request
	 * @param null $extra
	 * @return mixed
	 */
	public function build(Request $request, $extra = null);

    /**
     * @param array $eventProperties
     * @return mixed
     */
	public function eventDispatch(array $eventProperties = null);
}