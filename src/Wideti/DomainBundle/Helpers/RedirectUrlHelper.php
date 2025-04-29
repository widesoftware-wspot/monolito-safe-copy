<?php

namespace Wideti\DomainBundle\Helpers;

class RedirectUrlHelper
{
    public function isValid($url = null)
    {
        if (empty($url)) {
            return false;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    public function getValidUrl($url)
    {
        if ($this->isValid($url)) {
            return $url;
        }

        $url = "https://" . $url;

        if ($this->isValid($url)) {
            return $url;
        }
    }
}
