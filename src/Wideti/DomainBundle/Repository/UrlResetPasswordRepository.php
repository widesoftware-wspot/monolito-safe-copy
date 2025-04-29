<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;

class UrlResetPasswordRepository extends EntityRepository
{
    public function getOneUrlResetPassword($url)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select()
            ->where('u.username = :url')
            ->setParameter('url', $url)
            ->getQuery();

        return $q->getResult();
    }
}