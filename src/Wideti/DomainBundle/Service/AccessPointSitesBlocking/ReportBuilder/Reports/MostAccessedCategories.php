<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\ApiRequestService;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\ApiRequestServiceImp;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\Dto\ReportBuilder;

class MostAccessedCategories implements Report
{
    /**
     * @var ApiRequestService
     */
    private $apiRequestService;

    /**
     * MostAccessedCategories constructor.
     * @param ApiRequestService $apiRequestService
     */
    public function __construct(ApiRequestService $apiRequestService)
    {
        $this->apiRequestService = $apiRequestService;
    }

    public function process(AccessPoints $accessPoint)
    {
        $url = "/top-accessed-categories/access-points/{$accessPoint->getId()}/clients/{$accessPoint->getClient()->getId()}";
        $response   = $this->apiRequestService->request($url);
        $data       = TransformDataHelper::transformToPlotGraph($response);

        $builder = new ReportBuilder();
        return $builder
            ->withReport(ReportBuilder::REPORT_MOST_ACCESSED_CATEGORIES)
            ->withData($data)
            ->build();
    }
}
