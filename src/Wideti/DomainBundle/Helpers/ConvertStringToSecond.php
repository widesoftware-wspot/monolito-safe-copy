<?php

namespace Wideti\DomainBundle\Helpers;

abstract class ConvertStringToSecond
{
    public static function convert($time)
    {
        $array = explode(" ", strtoupper($time));

        $convertedArray = array(
            'days'     => '',
            'hours'    => '',
            'minutes'  => '',
            'seconds'  => ''
        );

        foreach ($array as $value) {
            if (substr($value, -1) == 'D') {
                $convertedArray['days'] = str_replace('D', '', $value);
            }

            if (substr($value, -1) == 'H') {
                $convertedArray['hours'] = str_replace('H', '', $value);
            }

            if (substr($value, -1) == 'M') {
                $convertedArray['minutes'] = str_replace('M', '', $value);
            }

            if (substr($value, -1) == 'S') {
                $convertedArray['seconds'] = str_replace('S', '', $value);
            }
        }

        $daysToSecond       = ($convertedArray['days'] * 24) * 60 * 60;
        $hoursToSecond      = $convertedArray['hours'] * 60 * 60;
        $minutesToSecond    = $convertedArray['minutes'] * 60;

        $convertedTime      = $daysToSecond + $hoursToSecond + $minutesToSecond + $convertedArray['seconds'];

        return $convertedTime;
    }

    public static function convertPeriod($time)
    {
        $array = explode(" ", strtoupper($time));

        $convertedArray = array(
            'days'     => '',
            'hours'    => '',
            'minutes'  => '',
            'seconds'  => ''
        );

        foreach ($array as $value) {
            if (substr($value, -1) == 'D') {
                $convertedArray['days'] = str_replace('D', '', $value);
            }

            if (substr($value, -1) == 'H') {
                $convertedArray['hours'] = str_replace('H', '', $value);
            }

            if (substr($value, -1) == 'M') {
                $convertedArray['minutes'] = str_replace('M', '', $value);
            }

            if (substr($value, -1) == 'S') {
                $convertedArray['seconds'] = str_replace('S', '', $value);
            }
        }

        $stringInterval = "P";

        if ($convertedArray['days'] != "") {
            $stringInterval .= $convertedArray['days'] . "D";
        }

        if (
            $convertedArray['hours'] != "" ||
            $convertedArray['minutes'] != "" ||
            $convertedArray['seconds'] != ""
        ) {
            $stringInterval .= "T";
        }

        if ($convertedArray['hours'] != "") {
            $stringInterval .= $convertedArray['hours'] . "H";
        }

        if ($convertedArray['minutes'] != "") {
            $stringInterval .= $convertedArray['minutes'] . "M";
        }

        if ($convertedArray['seconds'] != "") {
            $stringInterval .= $convertedArray['seconds'] . "S";
        }
        return $stringInterval;
    }

    public static function dateInterval($time)
    {
        $result  = "PT" . (int)$time . "S";
        return $result;
    }
}
