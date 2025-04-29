<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

class UnifinewFields implements RequiredFields
{
    const SITE_KEY = 'site';
    const LOGIN_URL_KEY = 'login_url';
    const AUTHORIZE_ERROR_URL = 'authorize_error_url';

    private $rawParameters;
    public function __construct(array $rawParameters)
    {
        $this->rawParameters = $rawParameters;
    }

    public function getApMacFields()
    {
        return ['ap_mac'];
    }

    public function getGuestMacFields()
    {
        return ['client_mac'];
    }

    public function getNasUrlPostFields()
    {
        return ['login_url'];
    }
}