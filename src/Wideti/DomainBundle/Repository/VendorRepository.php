<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Vendor;

class VendorRepository extends EntityRepository
{
    public function findAllVendors()
    {
        return $this->findBy(array(), array('vendor' => 'ASC'));
    }
}



