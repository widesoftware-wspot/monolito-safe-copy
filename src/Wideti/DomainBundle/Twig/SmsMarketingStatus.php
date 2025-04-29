<?php

namespace Wideti\DomainBundle\Twig;

class SmsMarketingStatus extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('translate_status', array($this, 'translate')),
        );
    }

    public function translate($slug)
    {
        $status = [
            "DRAFT" => "RASCUNHO",
            "PROCESSING" => "PROCESSANDO",
            "SENT" => "ENVIADO",
            "REMOVED" => "REMOVIDO"
        ];

        return isset($status[$slug]) ? $status[$slug] : "N/I";
    }

    public function getName()
    {
        return 'translate_status';
    }
}
