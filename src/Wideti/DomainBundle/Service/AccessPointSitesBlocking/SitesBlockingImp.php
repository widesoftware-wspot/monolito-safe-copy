<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\ApiRequestService;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\ReportHandler;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\BlockedCategories;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\BlockedDomains;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\MostAccessedCategories;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\MostAccessedDomains;

class SitesBlockingImp implements SitesBlocking
{
    /**
     * @var ApiRequestService
     */
    private $apiRequestService;

    /**
     * SitesBlockingImp constructor.
     * @param ApiRequestService $apiRequestService
     */
    public function __construct(ApiRequestService $apiRequestService)
    {
        $this->apiRequestService = $apiRequestService;
    }

    public function report(AccessPoints $accessPoint)
    {
        $reportHandler = new ReportHandler();
        $reportHandler->addReport(new BlockedCategories($this->apiRequestService));
        $reportHandler->addReport(new MostAccessedCategories($this->apiRequestService));
        $reportHandler->addReport(new BlockedDomains($this->apiRequestService));
        $reportHandler->addReport(new MostAccessedDomains($this->apiRequestService));

        return $reportHandler->process($accessPoint);
    }
}
