<?php

namespace Wideti\DomainBundle\Service\User;

use Wideti\DomainBundle\Service\User\UserService;

/**
 *
 * Usage: - [ setUserService, ["@core.service.user"] ]
 */
trait UserServiceAware
{
    /**
     * @var UserService
     */
    protected $userService;

    public function setUserService(UserService $service)
    {
        $this->userService = $service;
    }
}
