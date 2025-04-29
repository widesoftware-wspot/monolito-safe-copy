<?php


namespace Wideti\DomainBundle\Service\AccessCode\Assert;


use Wideti\DomainBundle\Exception\NotExistsAccessCodeLotException;

class AssertEnableAccessCode
{
    public static function validateAccessCode ($statusCodeSize)
    {
        if (empty($statusCodeSize)) {
            throw new NotExistsAccessCodeLotException();
        }

        return true;
    }
}