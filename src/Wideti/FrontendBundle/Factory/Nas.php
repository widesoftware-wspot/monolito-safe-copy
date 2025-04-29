<?php

namespace Wideti\FrontendBundle\Factory;

use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicy;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;

interface Nas
{
    const NAS_SESSION_KEY = 'wspotNas';

    const EXTRA_PARAM_SSID          = 'ssid';
    const EXTRA_PARAM_REDIRECT_URL  = 'redirectUrl';
    const EXTRA_PARAM_TOKEN         = 'token';
    const EXTRA_PARAM_MAGIC         = 'magic';
    const EXTRA_PARAM_PROXY         = 'proxy';
    const EXTRA_PARAM_USER_IP       = 'userip';
    const EXTRA_PARAM_QV            = 'Qv';
    const EXTRA_PARAM_AUTHORIZE_ERROR_URL = "authorize_error_url";

    /**
     * @return string
     */
    public function getAccessPointMacAddress();

    /**
     * @return string
     */
    public function getGuestDeviceMacAddress();

    /**
     * @return string
     */
    public function getVendorName();

    /**
     * @param $key
     * @return string
     */
    public function getExtraParam($key);

    /**
     * @return array
     */
    public function getExtraParameters();

    /**
     * @return NasFormPostParameter
     */
    public function getNasFormPost();

    /**
     * @return array
     */
    public function getVendorRawParameters();

    /**
     * @param RadiusPolicy $radiusPolicy
     * @return void
     */
    public function setRadiusPolicy(RadiusPolicy $radiusPolicy);

    /**
     * @return RadiusPolicy
     */
    public function getRadiusPolicy();

    /**
     * @return string
     */
    public function getAuthorizeErrorUrl();
}
