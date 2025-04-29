<?php
namespace Wideti\DomainBundle\Service\AuthorizationCode;

trait AuthorizationCodeServiceAware
{
    /**
     * @var AuthorizationCodeService
     */
    protected $authorizationCodeService;

    public function setAuthorizationCodeService(AuthorizationCodeService $authorizationCodeService)
    {
        $this->authorizationCodeService = $authorizationCodeService;
    }
}
