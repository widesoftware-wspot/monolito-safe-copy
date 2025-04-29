<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\SecurityContextInterface;

trait SecurityAware
{
    /**
     * @var TokenStorage
     */
    protected $securityContext;

    public function getUser()
    {
        if (null === $token = $this->securityContext->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    public function setSecurityContext(TokenStorage $securityContext)
    {
        $this->securityContext = $securityContext;
    }
}
