<?php

namespace Wideti\DomainBundle\Twig;

class DateToStrToTime extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('strtotime', [$this, 'convert']),
        ];
    }

    public function convert($date)
    {
        return strtotime(date_format($date, 'Y-m-d H:i:s'));
    }

    public function getName()
    {
        return 'strtotime';
    }
}
