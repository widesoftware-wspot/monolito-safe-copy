<?php

namespace Wideti\DomainBundle\Service\TwoFactorAuth;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\TwoFactorAuth\Dto\ResponseAuthorization;

interface TwoFactorAuthService
{
    /**
     * @return boolean
     */
    public function isModuleActive();

    /**
     * @param $fieldValue
     * @return ResponseAuthorization
     */
    public function isAuthorized($fieldValue);

	/**
	 * @param Client $client
	 * @param $shortcode
	 * @return mixed
	 */
    public function getTwoFactorAuthObject($shortcode);
}
