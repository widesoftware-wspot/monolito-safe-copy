<?php

namespace Wideti\DomainBundle\Service\User;

use Monolog\Logger;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Repository\UsersRepository;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Erp\ErpService;
use Wideti\DomainBundle\Entity\Users;

class UserServiceProxy extends UserService
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * UserServiceProxy constructor.
     * @param ConfigurationService $configurationService
     * @param ControllerHelper $controllerHelper
     * @param $whiteLabel
     * @param ErpService $erpService
     * @param CacheServiceImp $cacheService
     * @param ClientService $clientService
     * @param UsersRepository $usersRepository
     * @param Logger $logger
     */
    public function __construct(
        ConfigurationService $configurationService,
        ControllerHelper $controllerHelper,
        $whiteLabel,
        ErpService $erpService,
        CacheServiceImp $cacheService,
        ClientService $clientService,
        UsersRepository $usersRepository,
        Logger $logger
    ) {
        parent::__construct(
            $configurationService,
            $controllerHelper,
            $whiteLabel,
            $erpService,
            $cacheService,
            $clientService,
            $usersRepository
        );
        $this->logger = $logger;
    }

    public function register(Users $user, $autoPassword)
    {
        parent::register($user, $autoPassword);
        return $user;
    }

    public function createPassword(Users $user)
    {
        parent::createPassword($user);
    }

    public function changePassword(Users $user, $data, $oldPassword = null)
    {
        parent::changePassword($user, $data, $oldPassword);
    }

    public function update(Users $user)
    {
        parent::update($user);
    }

    public function delete(Users $user)
    {
        $user = parent::delete($user);

        $this->save($user);
    }

    public function resetPassword(Users $user = null, $isPanel = false)
    {
        parent::resetPassword($user, $isPanel);
    }
}
