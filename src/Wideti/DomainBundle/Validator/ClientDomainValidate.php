<?php

namespace Wideti\DomainBundle\Validator;

class ClientDomainValidate
{
    private $domain;

    private function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function validate($domain)
    {
        if (!$domain) {
            return true;
        }

        $this->setDomain($domain);

        $rejectDomain = array(
            'workers'
        );

        if (in_array($domain, $rejectDomain)) {
            return false;
        }

        return true;
    }
}
