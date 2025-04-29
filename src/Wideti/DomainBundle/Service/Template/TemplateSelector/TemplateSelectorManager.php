<?php

namespace Wideti\DomainBundle\Service\Template\TemplateSelector;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\FrontendBundle\Factory\Nas;

class TemplateSelectorManager implements TemplateSelector
{
    /**
     * @var TemplateSelector[]
     */
    private $selectors;

    /**
     * @param Nas $nas
     * @param Client $client
     * @param Campaign $campaign
     * @return Template
     */
    public function select(Nas $nas = null, Client $client, Campaign $campaign = null)
    {
        $template = null;
        foreach ($this->selectors as $selector) {
            $template = $selector->select($nas, $client, $campaign);
            if ($template) {
                break;
            }
        }
        return $template;
    }

    public function AddSelector(TemplateSelector $selector)
    {
        $this->selectors[] = $selector;
    }
}
