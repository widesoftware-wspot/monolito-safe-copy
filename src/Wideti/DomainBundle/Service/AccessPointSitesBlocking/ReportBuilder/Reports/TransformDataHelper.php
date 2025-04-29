<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports;

abstract class TransformDataHelper
{
    public static function transformToPlotGraph($response)
    {
        $report = [];
        foreach ($response as $item) {
            array_push($report, [
                'label' => $item['key'],
                'data' => $item['value']
            ]);
        }
        return $report;
    }
}
