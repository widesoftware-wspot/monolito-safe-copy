<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

abstract class ElasticSearchIndexHelper
{
	/**
	 * @param array $period
	 * @return string
	 * @throws \Exception
	 */
    public static function getIndex($period = [])
    {
        if (empty($period)) {
            return ElasticSearch::CURRENT;
        }

        if (array_key_exists('from', $period) && array_key_exists('to', $period)) {
            $dateFrom   = new \DateTime($period['from']);
            $dateTo     = new \DateTime($period['to']);
        }

        if (array_key_exists('date_from', $period) && array_key_exists('date_to', $period)) {
            $dateFrom   = new \DateTime($period['date_from']);
            $dateTo     = new \DateTime($period['date_to']);
        }

        if (array_key_exists('period', $period)) {
            $dateFrom   = new \DateTime("NOW -{$period['period']} DAY");
	        $dateTo     = new \DateTime('NOW');
	    }

	    $indexes = [];
	    array_push($indexes, "wspot_" . $dateFrom->format('Y_m'));
	    $diff    = $dateFrom->diff($dateTo)->days;

	    for ($i=1; $i<=$diff; $i++) {
		    $dateFrom->add(new \DateInterval("P1D"));
		    array_push($indexes, "wspot_" . $dateFrom->format('Y_m'));
	    }

	    return implode(',', array_unique($indexes));
    }

    public static function getReportIndex($period, $reportType)
    {
        if (empty($period)) {
            return "{$reportType}_" . ElasticSearch::ALL;
        }

        if (array_key_exists('from', $period) && array_key_exists('to', $period)) {
            $dateFrom = new \DateTime($period['from']);
        }

        if (array_key_exists('date_from', $period) && array_key_exists('date_to', $period)) {
            $dateFrom = new \DateTime($period['date_from']);
        }

        if (array_key_exists('period', $period)) {
            $dateFrom = new \DateTime('NOW -'.$period['period'].' DAY');
        }

        $dateTo   = new \DateTime('NOW');
        $dateDiff = $dateTo->diff($dateFrom);

        if ($dateFrom->format('m') == $dateTo->format('m') &&
            $dateFrom->format('y') == $dateTo->format('y')) {
	        return "{$reportType}_{$dateFrom->format('Y')}_{$dateFrom->format('m')}";
        }

        $days = $dateDiff->days;

		$reports = [
			'report_visits_registrations_per_ap',
			'report_visits_registrations_per_hour',
			'report_download_upload',
		];

		if (in_array($reportType, $reports)) {
			return ($days <= 30)
				? "{$reportType}_{$dateFrom->format('Y')}_{$dateFrom->format('m')},{$reportType}_{$dateTo->format('Y')}_{$dateTo->format('m')}"
				: "{$reportType}_" . ElasticSearch::ALL;
		}

        return ($days <= 30)
            ? "{$reportType}_" . ElasticSearch::LAST_30_DAYS
            : "{$reportType}_" . ElasticSearch::ALL;
    }
}
