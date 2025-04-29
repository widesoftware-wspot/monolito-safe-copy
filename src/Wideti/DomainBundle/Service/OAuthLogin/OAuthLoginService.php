<?php

namespace Wideti\DomainBundle\Service\OAuthLogin;

use Wideti\DomainBundle\Entity\OAuthLogin;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class OAuthLoginService
{
    use EntityManagerAware;
    use SessionAware;

    /**
     * @var OAuthLogin
     */
    protected $oauthlogin;
    public function create(OAuthLogin $oauthlogin)
    {
        $this->em->persist($oauthlogin);
        $this->em->flush();

        return $oauthlogin;
    }

    /**
     * @var OAuthLogin
     */
    public function update(OAuthLogin $oauthlogin)
    {
        $this->em->persist($oauthlogin);
        $this->em->flush();

        return $oauthlogin;
    }

    /**
     * @param OAuthLogin
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($oauthlogin) {
        $this->em->remove($oauthlogin);
        $this->em->flush();
    }

}