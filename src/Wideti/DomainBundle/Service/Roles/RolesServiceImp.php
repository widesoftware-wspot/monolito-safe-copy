<?php

namespace Wideti\DomainBundle\Service\Roles;

use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Roles\RolesService;

/**
 * Class RolesServiceImp
 * @package Wideti\DomainBundle\Service\Roles
 */
class RolesServiceImp implements RolesService
{
    /**
     * @param Users $user
     * @return bool
     */
    public function isRoleManagerUser(Users $user)
    {
        $roleObj = $user->getRole();
        $userRole = $roleObj->getRole();
        return ($userRole === Roles::ROLE_MANAGER) ? true : false;
    }

}