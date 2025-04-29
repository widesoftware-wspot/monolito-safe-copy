<?php

namespace Wideti\DomainBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ReservedDomainRepository extends EntityRepository
{
    /**
     * @param string $domain
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function domainExists($domain = '')
    {
        Assert::notEmpty($domain, 'Domain can`t be null');
        $reservedDomain = $this->findOneBy(['domain' => $domain]);
        return $reservedDomain !== null;
    }
}
