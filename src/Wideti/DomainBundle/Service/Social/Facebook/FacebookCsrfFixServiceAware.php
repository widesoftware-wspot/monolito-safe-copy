<?php

namespace Wideti\DomainBundle\Service\Social\Facebook;

trait FacebookCsrfFixServiceAware
{
    /**
     * @var FacebookCsrfFixService
     */
    protected $facebookCsrfFix;

    public function setFacebookCsrfFix(FacebookCsrfFixService $csrfFixService)
    {
        $this->facebookCsrfFix = $csrfFixService;
    }
}
