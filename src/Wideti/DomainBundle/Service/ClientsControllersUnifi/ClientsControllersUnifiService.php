<?php

namespace Wideti\DomainBundle\Service\ClientsControllersUnifi;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientsControllersUnifi;
use Wideti\DomainBundle\Exception\ErrorOnCreateAccessPointException;
use Wideti\DomainBundle\Repository\ClientsControllersUnifiRepository;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

/**
 * Class ClientsControllersUnifiService
 * @package Wideti\DomainBundle\Service\ClientsControllersUnifi
 */
class ClientsControllersUnifiService
{
    use EntityManagerAware;
    use SessionAware;

	/**
     * @var ClientsControllersUnifiRepository
     */
	private $clientsControllersUnifiRepository;

    /**
     * @var Logger
     */
	private $logger;

    /**
     * AccessPointsService constructor.
     * @param ClientsControllersUnifiRepository $clientsControllersUnifiRepository
     * @param Logger $logger
     */
	public function __construct(
	    ClientsControllersUnifiRepository $clientsControllersUnifiRepository,
        Logger $logger
    )
	{
		$this->clientsControllersUnifiRepository = $clientsControllersUnifiRepository;
		$this->logger = $logger;
	}

    /**
     * @param $clientController
     * @throws DBALException
     */
	protected function checkIfClientControllerExists($clientController)
    {
        $unifiId = $clientController->getUnifiId();
        $clientId = $clientController->getClientId();
        $check = null;

        $check = $this->em
            ->getRepository('DomainBundle:ClientsControllersUnifi')
            ->getClientsControllersByUnique($unifiId, $clientId);

        if ($check) {
            throw new DBALException('uk_clients_controllers_unifi');
        }
    }

    /**
     * @param ClientsControllersUnifi $clientController
     * @throws DBALException
     * @throws ControllerUnifiUniqueException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(ClientsControllersUnifi $clientController)
    {
        $this->checkIfClientControllerExists($clientController);

        $ctrl = $this->em
            ->getRepository("DomainBundle:ClientsControllersUnifi")
            ->getClientsControllersByClient($clientController->getClientId());
        if (count($ctrl) > 0) {
            $ctrl = $ctrl[0];
            $ctrl->setUnifiId($clientController->getUnifiId());
        }
        else {
            $this->em->persist($clientController);
        }
        
        try {
            $this->em->flush();
        } catch (DBALException $e) {
            $message = "Duplicate entry";

            if (is_int(strpos($e->getPrevious()->getMessage(), 'uk_clients_controllers_unifi'))) {
                $message = 'uk_clients_controllers_unifi';
            }

            throw new DBALException($message);
        }
    }

    /**
     * @param ClientsControllersUnifi $clientController
     * @throws DBALException
     * @throws ControllerUnifiUniqueException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(ClientsControllersUnifi $clientController)
    {
        $ctrl = $this->em
            ->getRepository("DomainBundle:ClientsControllersUnifi")
            ->getClientsControllersByClient($clientController->getClientId());
        $ctrl = $ctrl[0];
        $ctrl->setUnifiId($clientController->getUnifiId());
        
        try {
            $this->em->flush();
        } catch (DBALException $e) {
            $message = "Duplicate entry";

            if (is_int(strpos($e->getPrevious()->getMessage(), 'uk_clients_controllers_unifi'))) {
                $message = 'uk_clients_controllers_unifi';
            }

            throw new DBALException($message);
        }
    }

    /**
     * @param ClientsControllersUnifi $clientController
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(ClientsControllersUnifi $clientController)
    {
        try {
            $this->em->remove($clientController);
            $this->em->flush();
        } catch (DBALException $error) {
            throw new DBALException($error->getMessage());
        }
    }
}
