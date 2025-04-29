<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Exception\AccessPointExistsException;
use Wideti\DomainBundle\Exception\AccessPointNotRegisteredException;
use Wideti\DomainBundle\Exception\ErrorOnCreateAccessPointException;
use Wideti\DomainBundle\Exception\WrongAccessPointIdentifierException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Service\AccessPoints\Dto\AccessPointFilterDto;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\Vendor\VendorAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

/**
 * Class AccessPointsService
 * @package Wideti\DomainBundle\Service\AccessPoints
 */
class AccessPointsService
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use VendorAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use SessionAware;

	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;

    /**
     * @var AccessPointsRepository
     */
	private $accessPointsRepository;

    /**
     * @var Logger
     */
	private $logger;

    /**
     * AccessPointsService constructor.
     * @param CacheServiceImp $cacheService
     * @param AccessPointsRepository $accessPointsRepository
     * @param Logger $logger
     */
	public function __construct(
	    CacheServiceImp $cacheService,
        AccessPointsRepository $accessPointsRepository,
        Logger $logger
    )
	{
		$this->cacheService           = $cacheService;
		$this->accessPointsRepository = $accessPointsRepository;
		$this->logger                 = $logger;
	}

    /**
     * @param $station
     * @throws DBALException
     */
	protected function checkIfApExists($station)
    {
        $identifier = $station->getIdentifier();
        $check      = null;

        $check = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPointByIdentifier($identifier, $this->getLoggedClient())
        ;

        if ($check) {
            throw new DBALException('unique_identifier');
        }
    }

    /**
     * @param AccessPoints $station
     * @param AccessPointsGroups|null $group
     * @throws DBALException
     * @throws WrongAccessPointIdentifierException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(AccessPoints $station, AccessPointsGroups $group = null)
    {
        $this->checkIfApExists($station);

        if ($group !== null) {
            $station->setGroup($group);
        }

        if (!$this->isValidIdentifier($station)) {
            throw new WrongAccessPointIdentifierException(
                "O identificador do equipamento {$station->getVendor()} é inválido."
            );
        }

        $client = $this->em
            ->getRepository("DomainBundle:Client")
            ->find($this->getLoggedClient());

        if ($client == null) {
            throw new NotFoundException('Client not found');
        }

        $identifier = $station->getIdentifier();

        if ($station->getVendor() != Vendor::MIKROTIK) {
            $identifier = strtoupper($identifier);
        }

        $apVendor = $this->selectApVendor($station->getVendor());
        $station->setVendorId($apVendor);
        $station->setClient($client);
        $station->setIdentifier(NasHelper::makeIdentity($identifier));
        $this->em->persist($station);

        try {
            $this->em->flush();
        } catch (DBALException $e) {
            $message = "Duplicate entry";

            if (is_int(strpos($e->getPrevious()->getMessage(), 'unique_identifier'))) {
                $message = 'unique_identifier';
            }

            throw new DBALException($message);
        }

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
            $this->cacheService->removeAllConfigs();
        }
    }

    /**
     * @param AccessPoints $station
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(AccessPoints $station)
    {
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($station);

        if (array_key_exists('status', $changeset) && $changeset['status'][0] == false) {
            $this->checkIfApExists($station);
        }

        $accessPointPending = $this->em
            ->getRepository("DomainBundle:AccessPoints")
            ->findByfriendlyName('');

        $this->session->set('accessPointPendingName', count($accessPointPending));

        $this->em->persist($station);
        $this->em->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
            $this->cacheService->removeAllConfigs();
        }
    }

    /**
     * @param AccessPoints $station
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(AccessPoints $station)
    {
        $this->em->remove($station);
        $this->em->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
            $this->cacheService->removeAllConfigs();
        }
    }

    /**
     * @param $status
     * @param Client $client
     * @return array|\Wideti\DomainBundle\Entity\AccessPoints[]
     */
    public function getAllByStatus($status, Client $client)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findBy([
                "status" => $status,
                "client" => $client
            ]);
    }

    /**
     * @param $friendlyNames
     * @param $client
     * @return AccessPoints[]
     */
    public function findByFriendlyNames($friendlyNames, Client $client)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('ap');
        $qb->from('DomainBundle:AccessPoints', 'ap');
        $qb->where($qb->expr()->in('ap.friendlyName', $friendlyNames))
            ->andWhere($qb->expr()->eq('ap.client', $client->getId()));
        return $qb->getQuery()->getResult();
    }

    /**
     * @param AccessPoints $accessPoint
     * @return AccessPoints
     * @throws AccessPointExistsException
     * @throws ErrorOnCreateAccessPointException
     */
    public function createOne(AccessPoints $accessPoint)
    {
        $repository = $this->em->getRepository('DomainBundle:AccessPoints');
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $accessPoint->getClient()->getId()
        ]);
        $identifier = $accessPoint->getIdentifier();

        if ($repository->exists('identifier', $identifier, $client)) {
            throw new AccessPointExistsException(
                "Identificador do ponto de acesso \"{$identifier}\" já existe na base de dados"
            );
        }

        if (!$accessPoint->getTimezone()) {
            $accessPoint->setTimezone(TimezoneService::DEFAULT_TIMEZONE);
        }

        $status = $this->isReachedLimit($client) ? AccessPoints::INACTIVE : AccessPoints::ACTIVE;
        $accessPoint->setStatus($status);
        $accessPoint->setIdentifier(strtoupper($accessPoint->getIdentifier()));
        $accessPoint->setClient($client);
        $vendor = $this->vendor->getVendorByName($accessPoint->getVendor());
        $accessPoint->setVendorId($vendor);

        try {
            $this->em->persist($accessPoint);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new ErrorOnCreateAccessPointException($e->getMessage());
        }

        return $accessPoint;
    }

    /**
     * @param Client $client
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isReachedLimit(Client $client)
    {
        $count = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::ACTIVE
            ]);

        if ($count >= $client->getContractedAccessPoints()) {
            return true;
        }

        return false;
    }

    /**
     * @param Client $client
     * @param Nas $nas
     * @throws AccessPointNotRegisteredException
     */
    public function checkIfAreRegistered(Client $client, Nas $nas = null)
    {
        if (!$client->getApCheck()) return;

        $nasApIdentifier = $nas->getAccessPointMacAddress();
        $cacheIsActive   = $this->cacheService->isActive();

        if ($cacheIsActive && $this->cacheService->get($nasApIdentifier)) {
            return;
        }

        $ap = $this->em->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'client' => $client,
                'identifier' => $nasApIdentifier,
                'status' => 1
            ]);

        if (!$ap) {
            throw new AccessPointNotRegisteredException($nasApIdentifier);
        }

        if ($cacheIsActive) {
            $this->cacheService->set($nasApIdentifier, true, CacheServiceImp::TTL_AP_NOT_REGISTERED);
        }
    }

    /**
     * @param Nas|null $nas
     * @param Client $client
     * @return bool
     * @throws AccessPointNotRegisteredException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function verifyAccessPoint(Nas $nas = null, Client $client)
    {
        $accessPoint = $this->accessPointsRepository
            ->getAccessPoint($nas->getAccessPointMacAddress(), $client,false);

        if (!$accessPoint) {
            $this->handleAccessPointNotFound($nas, $accessPoint);
        }

        if ($accessPoint->isRequestVerified()) {
            return false;
        }

        return $this->saveAccessPointIfNotVerified($accessPoint);
    }

    /**
     * @param Nas $nas
     * @param AccessPoints|null $accessPoint
     * @throws AccessPointNotRegisteredException
     */
    private function handleAccessPointNotFound(Nas $nas, AccessPoints $accessPoint = null)
    {
        throw new AccessPointNotRegisteredException(
            "Access point: ({$nas->getAccessPointMacAddress()}) does not exist."
        );
    }

    /**
     * @param AccessPoints $accessPoint
     * @return bool
     */
    private function saveAccessPointIfNotVerified(AccessPoints $accessPoint)
    {
        try {
            $accessPoint
                ->setRequestVerified(true)
                ->setRadiusVerified(true)
                ->setVerifiedDate(new \DateTime());

            $this->accessPointsRepository->save($accessPoint);
        } catch (\Exception $e) {
            $this->logger
                ->addWarning("Problemas ao marcar AP como verificada: {$e->getMessage()}");

            return $e->getMessage();
        }
    }

    /**
     * @param AccessPointFilterDto $filter
     * @return AccessPoints[]
     */
    public function findByFilter(AccessPointFilterDto $filter)
    {
        $qb = $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->createQueryBuilder('ap')
            ->where('ap.client = :client')
            ->setParameter('client', $filter->getClient())
            ->setFirstResult($filter->getPage() * $filter->getLimit())
            ->setMaxResults($filter->getLimit())
            ->add('orderBy', new OrderBy('ap.created', 'DESC'));

        if ($filter->hasStatus()) {
            $qb
                ->andWhere('ap.status = :status')
                ->setParameter('status', $filter->getStatus());
        }

        if ($filter->hasIdentifier()) {
            $qb
                ->andWhere('ap.identifier = :identifier')
                ->setParameter('identifier', $filter->getIdentifier());
        }

        if ($filter->hasFriendlyName()) {
            $qb
                ->andWhere('ap.friendlyName LIKE :friendlyName')
                ->setParameter('friendlyName', "%{$filter->getFriendlyName()}%");
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param AccessPoints $accessPoint
     * @return boolean
     */
    private function isValidIdentifier(AccessPoints $accessPoint)
    {
        $hasMask = $this->vendor->hasMask(ucfirst($accessPoint->getVendor()));
        if (!$hasMask) return true;

        $defaultValidation = '/^((?:[a-zA-Z0-9]{2}[:-]){5}[a-zA-Z0-9]{2})$/'; // Mac address

        $validations = [
            'pfsense' => '/^(?:(?:\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/'
        ];

        $patternToMatch = isset($validations[$accessPoint->getVendor()]) ?
            $validations[$accessPoint->getVendor()] : $defaultValidation;

        return (bool) preg_match($patternToMatch, $accessPoint->getIdentifier());
    }

    /**
     * @param int $id
     * @param Client $client
     * @return AccessPoints
     */
    public function findByIdAndClient($id, Client $client)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'id' => $id,
                'client' => $client
            ]);
    }

    /**
     * @param $id
     * @return null|object|AccessPoints
     */
    public function findById($id)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'id' => $id
            ]);
    }

    /**
     * @param $identifier
     * @return null|object|AccessPoints
     */
    public function getAccessPointByIdentifier($identifier)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'identifier' => $identifier
            ]);
    }

    /**
     * @param $client
     * @throws DBALException
     */
    public function clearByClient($client)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->clearByClient($client);
    }

    public function hasApFromVendor(Client $client, $apVendor) {
        $apVendorListFromClient = $this->accessPointsRepository->getApVendorListFromClient($client);

        if(!empty($apVendorListFromClient)) {
            $hasVendor = array_filter($apVendorListFromClient, function($vendor) use ($apVendor) {
                return in_array($apVendor, $vendor);
            });
            return in_array(true, $hasVendor);
        }

        return false;
    }

    /**
     * @param $apVendor
     * @return Object|null
     */
    private function selectApVendor($apVendor) {
        return $this->vendor->getVendorByName(vendor::VENDOR_MAP[$apVendor]);
    }


    /**
     * @param int $id
     * @param Client $client
     * @return AccessPoints
     */
    public function findByClientAndIdentifier($clientId, $identifier)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'client'        => $clientId,
                'identifier'    => $identifier,
                'status'        => 1
            ]);
    }

    public function validateApOnFirstAccess(AccessPoints $ap)
    {
        $ap->setRequestVerified(true);
        $ap->setRadiusVerified(true);

        $ap->setVerifiedDate(new \DateTime());

        return $this
            ->em
            ->getRepository('DomainBundle:AccessPoints')
            ->save($ap);
    }
}
