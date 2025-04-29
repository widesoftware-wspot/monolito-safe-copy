<?php

namespace Wideti\DomainBundle\Helpers;

abstract class ConvertSecondToTime
{
    public static function convert($seconds)
    {
        $hours = floor($seconds / (60 * 60));

        $divisor_for_minutes = $seconds % (60 * 60);
        $minutes = floor($divisor_for_minutes / 60);

        $divisor_for_seconds = $divisor_for_minutes % 60;
        $seconds = ceil($divisor_for_seconds);

        $h = str_pad((int) $hours, 2, "0", STR_PAD_LEFT);
        $m = str_pad((int) $minutes, 2, "0", STR_PAD_LEFT);
        $s = str_pad((int) $seconds, 2, "0", STR_PAD_LEFT);

        return $h.":".$m.":".$s;
    }
}
