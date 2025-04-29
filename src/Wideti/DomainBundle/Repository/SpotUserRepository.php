<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;

class SpotUserRepository extends EntityRepository { }