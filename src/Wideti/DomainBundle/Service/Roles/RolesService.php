<?php

namespace Wideti\DomainBundle\Service\Roles;

use Wideti\DomainBundle\Entity\Users;

/**
 * Interface RolesService
 * @package Wideti\DomainBundle\Service\Roles
 */
interface RolesService
{
    /**
     * @param Users $user
     * @return bool
     */
    public function isRoleManagerUser(Users $user);

}
