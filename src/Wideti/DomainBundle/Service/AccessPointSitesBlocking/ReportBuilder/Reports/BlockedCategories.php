<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\ApiRequestService;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\ApiRequestServiceImp;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\Dto\ReportBuilder;

class BlockedCategories implements Report
{
    /**
     * @var ApiRequestService
     */
    private $apiRequestService;

    /**
     * BlockedCategories constructor.
     * @param ApiRequestService $apiRequestService
     */
    public function __construct(ApiRequestService $apiRequestService)
    {
        $this->apiRequestService = $apiRequestService;
    }

    public function process(AccessPoints $accessPoint)
    {
        $url = "/top-blocked-categories/access-points/{$accessPoint->getId()}/clients/{$accessPoint->getClient()->getId()}";
        $response   = $this->apiRequestService->request($url);
        $data       = TransformDataHelper::transformToPlotGraph($response);

        $builder = new ReportBuilder();
        return $builder
            ->withReport(ReportBuilder::REPORT_BLOCKED_CATEGORIES)
            ->withData($data)
            ->build();
    }


}
