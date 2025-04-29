<?php

namespace Wideti\DomainBundle\Helpers;

use DateTime;

class DateTimeHelper
{
    public static function daysOfWeek()
    {
        return [
            0 => "Domingo",
            1 => "Segunda-feira",
            2 => "Terça-feira",
            3 => "Quarta-feira",
            4 => "Quinta-feira",
            5 => "Sexta-feira",
            6 => "Sábado"
        ];
    }

    public static function getDayOfWeek($day)
    {
        switch ($day) {
            case 0:
                $day = 'Domingo';
                break;
            case 1:
                $day = 'Segunda-feira';
                break;
            case 2:
                $day = 'Terça-feira';
                break;
            case 3:
                $day = 'Quarta-feira';
                break;
            case 4:
                $day = 'Quinta-feira';
                break;
            case 5:
                $day = 'Sexta-feira';
                break;
            case 6:
                $day = 'Sábado';
                break;
        }
        return $day;
    }

    public static function timezoneOffset($remote, $origin = null)
    {
        if ($origin === null) {
            if (!is_string($origin = date_default_timezone_get())) {
                return false;
            }
        }

        $originDtz = new \DateTimeZone($origin);
        $remoteDtz = new \DateTimeZone($remote);

        $originDt = new \DateTime("now", $originDtz);
        $remoteDt = new \DateTime("now", $remoteDtz);

        $offset    = $originDtz->getOffset($originDt) - $remoteDtz->getOffset($remoteDt);

        return $offset * 1000;
    }

    public static function timezoneDifference()
    {
        return self::timezoneOffset("America/Sao_Paulo", "UTC");
    }

    public static function formatHourWithH($hour)
    {
        return str_pad($hour, 2, '0', STR_PAD_LEFT) . "h";
    }

    public static function formatHour($hour)
    {
        return str_pad($hour, 2, '0', STR_PAD_LEFT);
    }

    public static function formatHourPreProcessedReport($hour)
    {
        $explodeHour = explode(':', $hour);
        return $explodeHour[0] . "h";
    }

    public static function formatDate($date)
    {
        return date('Y-m-d H:i:s', strtotime($date));
    }

    public static function averageTimeFormat($time)
    {
        $begin  = new \DateTime("@0");
        $finish = new \DateTime("@" . (int)$time);
        $format = "";

        $diff = $begin->diff($finish);

        if ($diff->m > 0) {
            $format .= "%mm ";
        }

        if ($diff->d > 0) {
            $format .= "%Dd ";
        }

        if ($diff->h > 0) {
            $format .= "%Hh ";
        }

        if ($diff->i > 0) {
            $format .= "%Im ";
        }
        $format .= "%Ss";

        return $diff->format($format);
    }

    public static function timeToLiveCache()
    {
        $ttl = round((strtotime('23:59:59') - strtotime(date('H:i:s'))), 1);
        return $ttl;
    }

    /**
     * @param $period string
     * @return bool
     * @throws \Exception
     *
     * Este metodo valida o formato:
     * 1d 12h 5m que é usado na definição de tempo de acesso
     *
     */
    public static function validateTimePeriod($period)
    {
        $timeFragments = explode(" ", $period);
        $pattern = "/^[0-9]{1,3}[d,D,h,H,m,M,s,S]{1}$/";

        if (count($timeFragments) > 4) {
            return false;
        }

        foreach ($timeFragments as $fragment) {
            if (!preg_match($pattern, $fragment)) {
                return false;
            }
        }
        return true;
    }

    public static function convertDate($date, $formatFrom = "d/m/Y H:i", $formatTo = "Y-m-d H:i")
    {
        $dateTime = DateTime::createFromFormat($formatFrom, $date);
        return date($formatTo, $dateTime->getTimestamp());
    }

    public static function validateBirthdate($birthdate)
    {
        $dt         = new \DateTime(date('Y-m-d H:i:s', $birthdate->sec));
        $day        = (int) $dt->format('d');
        $month      = (int) $dt->format('m');
        $year       = (int) $dt->format('Y');
        $isValid    = true;

        if ($day < 1 || $day > 31) {
            $isValid = false;
        }

        if ($month < 1 || $month > 12) {
            $isValid = false;
        }

        if ($year < 1900 || $year > date('Y')) {
            $isValid = false;
        }

        return $isValid;
    }


    public static function validateAgeRestriction($birthdate)
    {
        $birthdate      = new \DateTime(date('Y-m-d', $birthdate->sec));
        $ageYears = $birthdate->diff(new \DateTime())->y;
        return $ageYears >= 18;
    }

    /**
     * @param DateTime $fromDate
     * @param string $period
     * @return DateTime
     */
    public static function getDateTimePlusPeriod(\DateTime $fromDate, $period)
    {
        $value = strtoupper($period);
        $value = str_replace(['D', 'H', 'M', 'S'], [' DAY', ' HOUR', ' MINUTE', ' SECOND'], $value);
        return $fromDate->modify($value);
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return bool
     * @throws \Exception
     */
    public static function handleDateTime($startTime, $endTime)
    {
        $initialTime = new DateTime($startTime);
        $initialTime->format("H:i");

        $finalTime = new DateTime($endTime);
        $finalTime->format("H:i");

        if ($startTime > $endTime) {
            return false;
        }

        if ($endTime == "00:00") {
            return false;
        }

        return true;
    }

    /**
     * @param $endDate
     * @return bool
     * @throws \Exception
     */
    public static function validateCampaignEndDate($endDate)
    {
        $date = new DateTime();
        $endDate = $endDate->format('Y-m-d');
        if ($endDate < $date->format('Y-m-d') ) {
            return false;
        }

        return true;
    }

    public static function secondsToMilleseconds($time)
    {
        if (!$time) return null;
        return (int) "{$time}000";
    }

    public static function convertDateTimeToUnixTimestamp($dateTime)
    {
        if (!$dateTime) return $dateTime;

        if ($dateTime instanceof \MongoDate) {
            return $dateTime->sec;
        }

        return $dateTime->getTimestamp();
    }

    public static function defineTimezoneAs($timezone, $dateTime)
    {
        $newDateTime = new \DateTime($dateTime, new \DateTimeZone("UTC"));
        return $newDateTime->setTimezone(new \DateTimeZone($timezone));
    }

    public static function defineAsUTC($dateTime)
    {
        if (!$dateTime) return null;
        return new \DateTime($dateTime, new \DateTimeZone("UTC"));
    }
}
