<?php

namespace Wideti\DomainBundle\Twig;

class DateDiffInDays extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('DateDiffInDays', array($this, 'DateDiffInDaysFilter')),
        );
    }

    /**
     * Returns the diff between some date and todays in day format.
     * @param $beginDate
     * @param null $endDate
     * @internal param $dateTimeFrom
     * @internal param null $dateTimeTo
     * @return string
     */
    public function dateDiffInDaysFilter($beginDate, $endDate = null)
    {
        if (!$endDate) {
            $endDate     = new \DateTime("NOW");
        }

        if (!is_object($beginDate)) {
            $beginDate   = new \DateTime($beginDate);
        }

        if (!is_object($endDate)) {
            $endDate     = new \DateTime($endDate);
        }

        $diff   = $beginDate->diff($endDate);
        $days   = $diff->days;

        if ($days === 1) {
            return "1 dia";
        }

        return "$days dias";

    }

    public function getName()
    {
        return 'wspot.twig.date_days_formatter';
    }
}
