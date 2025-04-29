<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Helpers\DateTimeHelper;

class FormatHour extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('format_hour', array($this, 'getFormatHour')),
        );
    }

    public function getFormatHour($hour)
    {
        return DateTimeHelper::formatHourWithH($hour);
    }

    public function getName()
    {
        return 'format_hour';
    }
}
