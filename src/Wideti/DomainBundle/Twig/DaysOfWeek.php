<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Helpers\DateTimeHelper;

class DaysOfWeek extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('days_of_week', array($this, 'getDaysOfWeek')),
        );
    }

    public function getDaysOfWeek($day)
    {
        return DateTimeHelper::getDayOfWeek($day);
    }

    public function getName()
    {
        return 'days_of_week';
    }
}
