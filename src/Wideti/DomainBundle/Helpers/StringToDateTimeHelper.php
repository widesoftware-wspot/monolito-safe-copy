<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Exception\DateInvalidException;

class StringToDateTimeHelper
{
    const DATE_FORMAT = "Y-m-d H:i:s";
    const TIME_ZONE = "America/Sao_Paulo";

    /**
     * @param $dateString
     * @param string $format
     * @return \DateTime
     * @throws DateInvalidException
     */
    public static function create($dateString, $format = self::DATE_FORMAT)
    {
        $timezone = new \DateTimeZone(self::TIME_ZONE);
        $from = \DateTime::createFromFormat($format, $dateString, $timezone);
        if (!$from) {
            throw new DateInvalidException("Data inválida: {$dateString}, formato correto: " . self::DATE_FORMAT);
        }
        return $from;
    }
}