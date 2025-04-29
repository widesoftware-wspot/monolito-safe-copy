<?php

namespace Wideti\DomainBundle\Helpers;

class ReportHelper
{
    public static function visitsAndRecordsPerDay($content)
    {
        $report = [];

        foreach ($content as $data) {
            $report['categories'][] = $data['key_as_string'];
            $report['signIns'][]    = $data['totalVisits']['value'];
            $report['signUps'][]    = $data['totalRegistrations']['value'];
        }

        return $report;
    }

    public static function mostAccessesHours($content)
    {
        if (empty($content)) {
            return [
                'hour' => '',
                'totalVisits' => '',
                'totalRegistrations' => ''
            ];
        }

        $report = [];

        foreach ($content['access_by_hour_visits']['buckets'] as $data) {
            $report['hour'][]        = substr($data['key'], 0, 2) . 'h';
            $report['totalVisits'][] = $data['totalVisits']['value'];
        }

        return $report;
    }

    public static function allAccessAndRegister($content)
    {
        if (empty($content)) {
            return [
              'date' =>'',
              'totalVisits' => '',
              'totalRegistration' => ''
            ];
        }

        $report = [];

        foreach ($content['daily_visits']['buckets'] as $data) {
            $report['categories'][]        = $data['key_as_string'];
            $report['signIns'][] = $data['totalVisits']['value'];
        }

        foreach ($content['daily_registrations']['buckets'] as $data) {
            $report['categories'][]        = $data['key_as_string'];
            $report['signUps'][] = $data['totalRegistrations']['value'];
        }

        return $report;
    }
}