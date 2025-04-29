<?php

namespace Wideti\DomainBundle\Twig;

class FormatAccessTime extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('formatAccessTime', array($this, 'applyTimeFormat'))
        );
    }

    public function applyTimeFormat($time)
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

    public function getName()
    {
        return 'format_access_time';
    }
}
