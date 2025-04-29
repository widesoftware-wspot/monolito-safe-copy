<?php

namespace Wideti\AdminBundle\Controller;

use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ApiDocsController
{
    use TwigAware;
    use SessionAware;

    public function indexAction()
    {
        $client = $this->getLoggedClient();

        $domain = "{$client->getDomain()}.mambowifi.com";
        if ($client->isWhiteLabel()) {
            $domain = $client->getDomain();
            return $this->render("AdminBundle:docs/DocsWL:index.html.twig",
                [
                    "client" => $client->getCompany(),
                    "domain" => $domain
                ]
            );
        }

        return $this->render("AdminBundle:docs:index.html.twig",
            [
                "client" => $client->getCompany(),
                "domain" => $domain
            ]
        );
    }
}