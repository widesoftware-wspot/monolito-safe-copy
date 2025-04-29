<?php

namespace Wideti\DomainBundle\Service\Analytics;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Service\Analytics\Dto\EventDto;

class AnalyticsServiceImp implements AnalyticsService
{
    /**
     * @var ControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var AnalyticsQueueService
     */
    private $queue;
    private $analyzer;
    private $analyticsActive;

    /**
     * AnalyticsServiceImp constructor.
     * @param ControllerHelper $controllerHelper
     * @param ContainerInterface $container
     * @param AnalyticsQueueService $queue
     * @param $analyticsActive
     * @param $analyzer
     */
    public function __construct(
        ControllerHelper $controllerHelper,
        ContainerInterface $container,
        AnalyticsQueueService $queue,
        $analyticsActive,
        $analyzer
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->container        = $container;
        $this->queue            = $queue;
        $this->analyticsActive  = $analyticsActive;
        $this->analyzer         = $analyzer;
    }

    /**
     * @param Request $request
     * @param array $extra
     * @throws \ReflectionException
     */
    public function handler(Request $request, $extra)
    {
        if (!$this->analyticsActive) return;

        $uri        = $request->getPathInfo();
        $route      = $this->controllerHelper->getRouter()->match($uri)['_route'];
        $method     = strtolower($request->getMethod());
        $routName   = str_replace(' ', '', ucwords(strtolower(preg_replace('/[^A-Za-z0-9]/', ' ', "{$method} {$route}")), "',. "));
        $className  = "Wideti\DomainBundle\Service\Analytics\Handlers\\" . $routName . "Handler";

        if ($extra) {
            $className  = "Wideti\DomainBundle\Service\Analytics\Handlers\Custom\\" . $routName . "Handler";
        }

        if (!class_exists($className)) return;

        $analyticsService = new AnalyticsServiceImp(
            $this->controllerHelper,
            $this->container,
            $this->queue,
            $this->analyticsActive,
            $this->analyzer
        );
        $parser  = new Parser();
        $clazz   = new \ReflectionClass($className);
        $handler = $clazz->newInstance($this->container, $analyticsService, $parser, $extra);

        return $handler->build($request, $extra);
    }

    public function sendEvent(EventDto $event)
    {
        $analyzer   = ucfirst($this->analyzer);
        $className  = "Wideti\DomainBundle\Service\Analytics\AnalysisTarget\\{$analyzer}";

        if (!class_exists($className)) return;

        $clazz      = new \ReflectionClass($className);
        $handler    = $clazz->newInstance($this->container);
        $content    = $handler->transform($event);

        $this->queue->sendToQueue($content);
    }
}
