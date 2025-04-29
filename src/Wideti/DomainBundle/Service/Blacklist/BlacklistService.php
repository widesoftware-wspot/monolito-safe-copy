<?php

namespace Wideti\DomainBundle\Service\Blacklist;

use Doctrine\DBAL\DBALException;
use Wideti\DomainBundle\Entity\Blacklist;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Repository\BlacklistRepository;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;

class BlacklistService
{
    use EntityManagerAware;
    use MongoAware;
    use LoggerAware;
    use PaginatorAware;
    use SecurityAware;
    use ModuleAware;

    /**
     * @var BlacklistRepository
     */
    private $blacklistRepository;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * BlacklistService constructor.
     * @param BlacklistRepository $blacklistRepository
     * @param GuestDevices $guestDevices
     */
    public function __construct(
        BlacklistRepository $blacklistRepository,
        GuestDevices $guestDevices
    ) {
        $this->blacklistRepository = $blacklistRepository;
        $this->guestDevices = $guestDevices;
    }

	/**
	 * @param Blacklist $blacklist
	 * @param $clientId
	 * @return Blacklist
	 * @throws DBALException
	 * @throws UniqueFieldException
	 */
    public function create(Blacklist $blacklist, $clientId)
    {
        $this->prepareToPersist($blacklist, $clientId);

        try {

            if ($this->blacklistRepository->findByMacAddress($blacklist->getMacAddress(), $blacklist->getClient())) {
                throw new UniqueFieldException('Mac address já bloqueado');
            }

            $this->blacklistRepository->saveOnBlackList($blacklist);


        } catch (\DBALException $e) {
            $this->logger->addCritical("Erro ao inserir blacklist" . $e->getMessage());
        }

        return $blacklist;
    }

    /**
     * @param string $macAddress
     * @return Blacklist
     */
    public function findByMacAddress($macAddress = "", $clientId)
    {
        try {
            $returnedBlacklist = $this->blacklistRepository->findByMacAddress($macAddress, $clientId);
            if ($returnedBlacklist) {
                return $this->prepareToUseBlacklist($returnedBlacklist) ;
            }

        } catch (DBALException $e) {
            $this->logger->addCritical("Error to find blacklist by mac: ".$e->getMessage());
        }

        return null;
    }

	/**
	 * @param Blacklist $blacklist
	 * @param $clientId
	 * @param null $oldMacValue
	 * @return Blacklist
	 * @throws DBALException
	 * @throws UniqueFieldException
	 */
    public function update(Blacklist $blacklist, $clientId, $oldMacValue = null)
    {
        $this->prepareToPersist($blacklist, $clientId);
        $blocked = $this->findByMacAddress($blacklist->getMacAddress(), $clientId);

        if (!$blocked || $blocked->getId() == $oldMacValue) {
            $this->blacklistRepository->update($blacklist, $oldMacValue);
        } else {
            throw new UniqueFieldException('Mac address já bloqueado');
        }

        return $blacklist;
    }

    /**
     * @param int $page
     * @param array $filters
     * @param int $offset
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function paginatedSearch($page = 1, $filters = [], $offset = 10, $clientId)
    {
        $blacklists =  $this->blacklistRepository->pagination($clientId, $filters);
        $pagination = $this->paginator->paginate($blacklists, $page, $offset);

        return $pagination;
    }

	/**
	 * @param $blacklist
	 * @throws DBALException
	 */
    public function delete($blacklist)
    {
        $this->blacklistRepository->deleteByMacAddres($blacklist->getMacAddress());
    }

	/**
	 * @param string $mac
	 * @param $clientId
	 * @return Blacklist
	 * @throws DBALException
	 * @throws UniqueFieldException
	 */
    public function blockByMacAddress($mac = "", $clientId)
    {
        if (!$mac) {
            throw new \InvalidArgumentException();
        }

        $blocked = $this->findByMacAddress($mac, $clientId);
        if ($blocked) {
            throw new UniqueFieldException('MacAddress já bloqueado');
        }

        $blacklist = new Blacklist();
        $blacklist->setMacAddress($mac);
        $blacklist = $this->create($blacklist, $clientId);

        return $blacklist;
    }

	/**
	 * @param string $mac
	 * @param $clientId
	 * @throws DBALException
	 */
    public function unblockByMacAddress($mac = "", $clientId)
    {
        if (!$mac) {
            throw new \InvalidArgumentException();
        }

        $blocked = $this->findByMacAddress($mac, $clientId);
        $this->delete($blocked);
    }

    /**
     * @param Guest $guest
     * @return array
     */
    public function getGuestBlockedDevices(Guest $guest, Client $client)
    {
        $guestMySql = $this->em->getRepository("DomainBundle:Guests")->findOneBy(['id' => $guest->getMysql()]);
        $devices    = $this->guestDevices->getDevices($guestMySql);

        $blockedDevices = [];

        /**
         * @var DeviceEntry $device
         */
        foreach ($devices as $device) {
            $macAddress = $device->getDevice()->getMacAddress();
            $blocked    = $this->findByMacAddress($macAddress, $client->getId());

            if ($blocked) {
                $blockedDevices[] = $blocked->getMacAddress();
            }
        }

        return $blockedDevices;
    }

    /**
     * @param Client $client
     * @param string $mac
     * @return \Doctrine\ODM\MongoDB\Query\Query|null
     */
    public function getAffectedGuestsBy(Client $client, $mac = "")
    {
        $guestsIdMysql = $this->guestDevices->getGuestsByMacDevice($client, $mac);

        if (empty($guestsIdMysql)) return null;

        $qb = $this->mongo->getRepository('DomainBundle:Guest\Guest')->createQueryBuilder();

        return $qb
            ->field('mysql')
            ->in($guestsIdMysql)
            ->getQuery();
    }

    public function isBlocked(Nas $nas = null, $clientId)
    {
        if (empty($nas->getGuestDeviceMacAddress())) {
            return false;
        }

        if ($this->moduleService->modulePermission('blacklist')) {
            $clientMac = $nas->getGuestDeviceMacAddress();
            $block = $this->findByMacAddress($clientMac, $clientId);
            return (bool) $block;
        }

        return false;
    }

	/**
	 * @param Blacklist $blacklist
	 * @param $clientId
	 * @throws \Exception
	 */
    private function prepareToPersist(Blacklist $blacklist, $clientId)
    {
        $date = new \DateTime('NOW');
        date_default_timezone_set("UTC");
        $blacklist->setCreated($date->format("y-m-d  H:i:s"));
        $blacklist->setMacAddress(strtoupper($blacklist->getMacAddress()));
        $blacklist->setCreatedBy($this->getUser()->getNome());
        $blacklist->setClient($clientId);
    }

	/**
	 * @param array $returnedBlacklist
	 * @return Blacklist
	 */
    private function prepareToUseBlacklist (array $returnedBlacklist) {
        $blackList = new Blacklist();
        $blackList->setClient($returnedBlacklist["client_id"]);
        $blackList->setCreated($returnedBlacklist["created"]);
        $blackList->setCreatedBy($returnedBlacklist["created_by"]);
        $blackList->setMacAddress($returnedBlacklist["mac_address"]);
        $blackList->setId($returnedBlacklist["id"]);

        return $blackList;
    }
}
