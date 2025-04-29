<?php

namespace Wideti\DomainBundle\Asserts;

use Wideti\DomainBundle\Exception\EmptyEntityException;

class EntitiesExistsAssert
{

    public static function exists($entities)
    {
        if (empty($entities)) {
            throw new EmptyEntityException();
            return false;
        }

        return true;
    }
}
