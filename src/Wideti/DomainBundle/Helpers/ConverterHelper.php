<?php

namespace Wideti\DomainBundle\Helpers;

class ConverterHelper
{
    public static function byteToGBorMB($bytes)
    {
        $mb = ($bytes / 1024 / 1024);

        if ($mb >= 100000000) {
            $result = number_format(($mb/1024/1024/1024), 0, '.', '').' PB';
        } elseif ($mb >= 1000000 && $mb <= 100000000) {
            $result = number_format(($mb/1024/1024), 0, '.', '').' TB';
        } elseif ($mb >= 1024 && $mb <= 1000000) {
            $result = number_format(($mb/1024), 0, '.', '').' GB';
        } elseif (substr($mb, 0, 1) != 0) {
            $result = number_format($mb, 0, '.', '').' MB';
        } else {
            $result = number_format($mb, 2, '.', '').' MB';
        }

        return $result;
    }

    public static function getStringMonth($month)
    {
        switch ($month) {
            case 1:
                $month = 'Janeiro';
                break;
            case 2:
                $month = 'Fevereiro';
                break;
            case 3:
                $month = 'Março';
                break;
            case 4:
                $month = 'Abril';
                break;
            case 5:
                $month = 'Maio';
                break;
            case 6:
                $month = 'Junho';
                break;
            case 7:
                $month = 'Julho';
                break;
            case 8:
                $month = 'Agosto';
                break;
            case 9:
                $month = 'Setembro';
                break;
            case 10:
                $month = 'Outubro';
                break;
            case 11:
                $month = 'Novembro';
                break;
            case 12:
                $month = 'Dezembro';
                break;
        }

        return $month;
    }
}
