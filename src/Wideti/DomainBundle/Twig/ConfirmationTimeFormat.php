<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ConfirmationTimeFormat extends \Twig_Extension
{
    use SessionAware;
    use TranslatorAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('confirmationTimeFormat', array($this, 'applyConfirmationTimeFormat')),
        );
    }

    public function applyConfirmationTimeFormat($value)
    {
        $return = $this->convertTime($value);

        return $return;
    }

    public function convertTime($value)
    {
        $value          = strtolower($value);
        $connectionTime = $value;

        $translate = [
            $this->translator->trans('wspot.day'),
            $this->translator->trans('wspot.hour'),
            $this->translator->trans('wspot.minute')
        ];

        $value = str_replace(['d', 'h', 'm'], array_map(function ($value) use ($connectionTime) {
            if (preg_replace('/[^0-9]/', '', $connectionTime) == '1') {
                return ' ' . $value;
            }
            return ' ' . $value . 's';
        }, $translate), $value);

        return $value;
    }

    public function getName()
    {
        return 'confirmation_time_format';
    }
}
