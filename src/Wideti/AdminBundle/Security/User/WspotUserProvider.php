<?php

namespace Wideti\AdminBundle\Security\User;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Wideti\AdminBundle\Security\IDP\User;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\Users;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class WspotUserProvider implements UserProviderInterface
{
    use SessionAware;
    use EntityManagerAware;

	private $cacheService;

	public function __construct($cacheService) {
		$this->cacheService = $cacheService;
	}

	/**
	 * @throws OptimisticLockException
	 */
	public function loadUserByUsername($username)
    {
        $client = $this->getLoggedClient();

		$redisKey = 'login_block_' . $client->getDomain() . $username;
		$blockedRemainingTime = $this->cacheService->getTTL($redisKey);
		if($blockedRemainingTime > 0) {
			return;
		}

		$spotsManager = $this->loginSpotsManager($username, $client);
		if (!empty($spotsManager)) {
			return $spotsManager;
		}

        $userManager = $this->logInUserManager($username);
        if ($this->isValidUser($userManager)) {
            return $userManager;
        }
		
        $userManager = $this->logInUserSuportLimited($username);
        if ($this->isValidUser($userManager)) {
            return $userManager;
        }

		return $this->loginUserClient($username, $client);
    }

	/**
	 * @throws OptimisticLockException
	 */
	private function loginSpotsManager($username, Client $client) {
		// Carrega o usuário se ele possuir a flag spots manager true
    	$user = $this->em
			->getRepository('DomainBundle:Users')
			->findOneBy([
				'username' => $username,
				'spotManager' => true,
				'createdAtOauth' => 0,
				'erpId' => null
			]);

		if (empty($user)) {
			return null;
		}

		// Verifica se o usuário possui acesso ao painel através do qual ele esta tentando autenticar
		$spotUser = $this
			->em
			->getRepository('DomainBundle:SpotUser')
			->findOneBy([
				'clientId' => $client->getId(),
				'userId'   => $user->getId(),
				'createdAtOauth' => 0,
				'erpId' => null
			]);

		if (empty($spotUser)) {
			return null;
		}

		// Atualiza o status de logged para true e retorna
		$user->setSpotManagerLogged(true);
		$this->em->flush($user);
		return $user;
	}

    private function isValidUser($user) {
    	return !is_null($user) && is_null($user->getClient());
	}

    private function logInUserManager($username) {
		$roleManager = $this->em
			->getRepository('DomainBundle:Roles')
			->findOneBy(['role' => Roles::ROLE_MANAGER]);

		return $this->em
			->getRepository('DomainBundle:Users')
			->findOneBy(['username' => $username, 'role' => $roleManager, 'createdAtOauth' => 0, 'erpId' => null]);
	}

	private function logInUserSuportLimited($username) {
		$roleSuportLimited =  $this->em
			->getRepository('DomainBundle:Roles')
			->findOneBy(['role' => Roles::ROLE_SUPORT_LIMITED]);

		return $this->em
			->getRepository('DomainBundle:Users')
			->findOneBy(['username' => $username, 'role' => $roleSuportLimited, 'createdAtOauth' => 0, 'erpId' => null]);
	}


	private function loginUserClient($username, Client $client) {
		return $this->em
			->getRepository('DomainBundle:Users')
			->loginByClient($username, $client);
	}

    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->em->getRepository('DomainBundle:Users')->find($user->getId());
    }

    public function supportsClass($class)
    {
        if (!$class instanceof Users) {
            return false;
        }
        return true;
    }
}
