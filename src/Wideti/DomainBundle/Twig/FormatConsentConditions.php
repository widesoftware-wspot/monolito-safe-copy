<?php


namespace Wideti\DomainBundle\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wideti\DomainBundle\Gateways\Consents\Consent;

class FormatConsentConditions extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new TwigFunction('conditionsAsHTML', array($this, 'conditionsAsHTML')),
        );
    }

    public function conditionsAsHTML(Consent $consent) {

        if ($consent->getHasError()) {
            return "<p style='color: red; font-weight: bolder'>Error on load the consentment conditions, try refresh the page</p>";
        }

        $htmlList = '<ul class="consent-conditions-list">';
        foreach ($consent->getConditions() as $c) {
            $htmlList .= '<li>' . $c->getDescription() . '</li>';
        }
        $htmlList .= '</ul>';
        return $htmlList;
    }
}