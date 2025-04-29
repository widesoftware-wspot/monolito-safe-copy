<?php

namespace Wideti\DomainBundle\Twig;

class DateExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('dateDiff', array($this, 'dateDiffFilter')),
        );
    }

    public function dateDiffFilter($dateTimeFrom, $dateTimeTo = null)
    {
        if (!is_object($dateTimeFrom)) {
            $dateTimeFrom   = new \DateTime($dateTimeFrom);
            $dateTimeTo     = new \DateTime($dateTimeTo);
        }

        $interval = (!$dateTimeTo) ? $dateTimeFrom->diff(new \DateTime("NOW")) : $dateTimeFrom->diff($dateTimeTo);
        $days   = $interval->format('%d');
        $month   = $interval->format('%m');
        $hours   = $interval->format('%h');
        $minutes = $interval->format('%i');
        $seconds = $interval->format('%s');
        $return = "";

        if ($month > 0) {
            $return .= ($month == 1) ? $month." mês " : $month." meses ";
        }

        if ($days > 0) {
            $return .= ($days == 1) ? $days." dia " : $days." dias ";
        }

        $return .= $hours."h ".$minutes."m ".$seconds."s";

        return $return;
    }

    public function getName()
    {
        return 'date_extension';
    }
}
