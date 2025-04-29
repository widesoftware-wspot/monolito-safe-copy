<?php

namespace Wideti\DomainBundle\Service\ControllersUnifi;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ControllersUnifi;
use Wideti\DomainBundle\Exception\AccessPointExistsException;
use Wideti\DomainBundle\Exception\AccessPointNotRegisteredException;
use Wideti\DomainBundle\Exception\ErrorOnCreateAccessPointException;
use Wideti\DomainBundle\Exception\ControllerUnifiUniqueException;
use Wideti\DomainBundle\Repository\ControllersUnifiRepository;
use Wideti\DomainBundle\Entity\ClientsControllersUnifi;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Service\ClientsControllersUnifi\ClientsControllersUnifiService;

/**
 * Class ControllersUnifiService
 * @package Wideti\DomainBundle\Service\ControllersUnifi
 */
class ControllersUnifiService
{
    use EntityManagerAware;
    use SessionAware;

	/**
     * @var ControllersUnifiRepository
     */
	private $controllersUnifiRepository;

	/**
     * @var ClientsControllersUnifiService
     */
	private $clientsControllersUnifiService;

    /**
     * @var Logger
     */
	private $logger;

    /**
     * AccessPointsService constructor.
     * @param ControllersUnifiRepository $controllersUnifiRepository
     * @param ClientsControllersUnifiService $clientsControllersUnifiService
     * @param Logger $logger
     */
	public function __construct(
	    ControllersUnifiRepository $controllersUnifiRepository,
	    ClientsControllersUnifiService $clientsControllersUnifiService,
        Logger $logger
    )
	{
		$this->controllersUnifiRepository = $controllersUnifiRepository;
		$this->clientsControllersUnifiService = $clientsControllersUnifiService;
		$this->logger = $logger;
	}

    /**
     * @param $controller
     * @throws DBALException
     */
	protected function checkIfControllerExists($controller)
    {
        $address = $controller->getAddress();
        $port = $controller->getPort();
        $check = null;

        $check = $this->em
            ->getRepository('DomainBundle:ControllersUnifi')
            ->getControllerByUnique($address, $port);

        if ($check) {
            throw new DBALException('uk_controllers_unifi');
        }
    }

    /**
     * @param ControllersUnifi $controller
     * @return ControllersUnifi $controller
     * @throws DBALException
     * @throws ControllerUnifiUniqueException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(ControllersUnifi $controller, $clientId)
    {
        $this->em->persist($controller);
        
        try {
            $this->em->flush();

            $unifiId = $controller->getId();
            $clientController = new ClientsControllersUnifi($unifiId, $clientId);
            
            $this->clientsControllersUnifiService->create($clientController);

            return $controller;
        } catch (DBALException $e) {
            $message = "Duplicate entry";

            if (is_int(strpos($e->getPrevious()->getMessage(), 'uk_controllers_unifi'))) {
                $message = 'uk_controllers_unifi';
            }

            throw new DBALException($message);
        }
    }

    /**
     * @param ControllersUnifi $controller
     * @param integer $clientId
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(ControllersUnifi $controller, $clientId)
    {
        try {
            $ctrl = $this->em->getRepository("DomainBundle:ControllersUnifi")->getControllerByClientId($clientId)[0];
            
            if ($ctrl->is_mambo == "1" && !$controller->getIsMambo()) {
                $this->em->getRepository("DomainBundle:ClientsControllersUnifi")->deleteByClientId($clientId);
                $this->create($controller, $clientId);
            }
            else {
                $ctrl->setIsMambo($controller->getIsMambo());
                $ctrl->setAddress($controller->getAddress());
                $ctrl->setPort($controller->getPort());
                $ctrl->setUsername($controller->getUsername());
                $ctrl->setPassword($controller->getPassword());

                $this->em->getRepository("DomainBundle:ControllersUnifi")->update($ctrl);
            }
        } catch (DBALException $error) {
            throw new DBALException($error->getMessage());
        }
    }

    /**
     * @param $clientId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getControllerByClientId($clientId)
    {
        $results = $this->em
            ->getRepository('DomainBundle:ControllersUnifi')
            ->getControllerByClientId($clientId);

        return $results;
    }

    /**
     * @param $id
     * @return ControllersUnifi
     * @throws DBALException
     */
    public function getControllerById($id)
    {
        $ctrl = $this->em->getRepository("DomainBundle:ControllersUnifi")->find($controller->getId());

        return $ctrl;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllUnifiControllersMamboActive()
    {
        $results = $this->em
            ->getRepository('DomainBundle:ControllersUnifi')
            ->queryAllUnifiControllersMamboActive();

        return $results;
    }
}
