<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Pagination;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Service\AccessPointsGroups\Dto\AccessPointGroupsFilterDto;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

/**
 * Class AccessPointsGroupsService
 * @package Wideti\DomainBundle\Service\AccessPointsGroups
 */
class AccessPointsGroupsService
{
    use EntityManagerAware;
    use SessionAware;
    use SecurityAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ClientRepository
     */
    private $clientRepository;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;
    /**
     * @var AccessPointsGroupsRepository
     */
	private $accessPointsGroupRepository;

    /**
     * AccessPointsGroupsService constructor.
     * @param ConfigurationService $configurationService
     * @param ClientRepository $clientRepository
     * @param CacheServiceImp $cacheService
     * @param AccessPointsGroupsRepository $accessPointsGroupsRepository
     */
    public function __construct(
    	ConfigurationService $configurationService,
	    ClientRepository $clientRepository,
		CacheServiceImp $cacheService,
        AccessPointsGroupsRepository $accessPointsGroupsRepository
    )
    {
        $this->configurationService        = $configurationService;
        $this->clientRepository            = $clientRepository;
	    $this->cacheService                = $cacheService;
	    $this->accessPointsGroupRepository = $accessPointsGroupsRepository;
    }

    /**
     * @param AccessPointsGroups $group
     * @param bool $dynamicConfiguration
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(AccessPointsGroups $group, $dynamicConfiguration = false, $aditionalInfo = [])
    {
        $client = $group->getClient();

        if ($client == null) {
            throw new NotFoundException('Client not found');
        }

        if (!$group->getParent()) {
            $group->setParentConfigurations(false);
            $group->setParentTemplate(false);
        }

        $group->setClient($client);
        $this->em->persist($group);

        if (!$dynamicConfiguration) {
            $this->configurationService->createDefaultConfiguration($client, $group,null,$aditionalInfo);
        } else {
            $this->em->flush();
        }

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
            $this->cacheService->removeAllConfigs();
        }
    }

    /**
     * @param AccessPointsGroups $group
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(AccessPointsGroups $group, $formData)
    {
        if ($group->getParent() == null) {
            $group->setParentConfigurations(false);
            $this->em->persist($group);
        }

        $currentGroupAccessPoints = $group->getAccessPoints();
        $client = $this->getLoggedClient();

        $defaultGroup = $this->em->getRepository('DomainBundle:AccessPointsGroups')
            ->hasDefaultGroup($client);

        if (!$defaultGroup) {
            throw new \Exception("Default group not found");
        }

        if (!$group->getParent()) {
            $group->setParentConfigurations(false);
            $group->setParentTemplate(false);
        }

        $repoAccessPoints = $this->em->getRepository('DomainBundle:AccessPoints');
        $qb = $repoAccessPoints->createQueryBuilder("a")
            ->select()
            ->where("a.client = :client")
            ->andWhere("a.group = :group")
            ->setParameter("client", $client->getId())
            ->setParameter("group", $group->getId())
        ;
        $accessPoints = $qb->getQuery()->getResult();

        foreach ($accessPoints as $ac) {
            $ac->setGroup($defaultGroup);
            $this->em->persist($ac);
        }

        foreach ($currentGroupAccessPoints as $ac)
        {
            $ac->setGroup($group);
            $this->em->persist($ac);
        }

        $this->em->persist($group);
        $this->em->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
            $this->cacheService->removeAllConfigs();
        }
    }

    /**
     * @param AccessPointsGroups $group
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(AccessPointsGroups $group)
    {
        $client = $this->clientRepository
            ->find($this->getLoggedClient())
        ;

        $groupId = $group->getId();

        $repoAccessPointsGroups = $this->em->getRepository('DomainBundle:AccessPointsGroups');
        $childs = $repoAccessPointsGroups->findChilds($group->getId(), $client);

        if ($childs) {
            throw new \Exception("Não é possível excluir esse grupo, pois ele possui subgrupos vinculados.");
        }

	    $this->configurationService->removeByGroup($client, $groupId);

	    $this->em->remove($group);
	    $this->em->flush();

	    if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
            $this->cacheService->removeAllConfigs();
        }
    }

    /**
     * @param $groupName
     * @param Client $client
     * @return null| AccessPointsGroups
     */
    public function getGroupByName($groupName, Client $client)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findOneBy([
                'client' => $client,
                'groupName' => trim($groupName)
            ]);
    }

    /**
     * @param $id
     * @param Client $client
     * @return null| AccessPointsGroups
     */
    public function getGroupByIdAndClient($id, Client $client)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findOneBy([
                'client' => $client,
                'id' => trim($id)
            ]);
    }

    /**
     * @param $id
     * @return null| AccessPointsGroups
     */
    public function getGroupById($id)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findOneBy([
                'id' => trim($id)
            ]);
    }

    /**
     * @param AccessPointGroupsFilterDto $filter
     * @return AccessPointsGroups[]
     */
    public function findByFilter(AccessPointGroupsFilterDto $filter)
    {
        $qb = $this
            ->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->createQueryBuilder('ap')
            ->where('ap.client = :client')
            ->setParameter('client', $filter->getClient())
            ->setFirstResult($filter->getPage())
            ->setMaxResults($filter->getLimit())
            ->add('orderBy', new OrderBy('ap.id', 'DESC'));

        if ($filter->hasGroupName()) {
            $qb
                ->andWhere('ap.groupName LIKE :groupName')
                ->setParameter('groupName', "%{$filter->getGroupName()}%");
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Client $client
     */
    public function clearByClient(Client $client)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->clearByClient($client);
    }

    /**
     * @param AccessPointsGroups $apsgs
     * @return mixed
     * @throws \Exception
     */
    public function getParentConfigurationGroupId(AccessPointsGroups $apsgs)
    {
        $parentConfiguration = $apsgs->getParentConfigurations();

        if ($parentConfiguration === true) {

            $client = $this->em
                ->getRepository("DomainBundle:Client")
                ->find($this->getLoggedClient());

            $parentAccessPointGroup = $this->em
                ->getRepository('DomainBundle:AccessPointsGroups')
                ->findOneBy([
                    'client' => $client,
                    'id' => trim($apsgs->getParent())
                ]);

            if (!$parentAccessPointGroup) {
                throw new \Exception("Configurações do access points não foram encontradas");
            }

            $id = $this->getParentConfigurationGroupId($parentAccessPointGroup);
        } else {
            return $apsgs->getId();
        }

        return $id;
    }

    /**
     * @param $apg
     * @return mixed
     * @throws \Exception
     */
    public function getParentTemplateByAccessPointsGroup($apg)
    {
        $parentTemplate = $apg->getParentTemplate();
        if ($parentTemplate === true) {

            $parentAccessPointGroup = $this->em
                ->getRepository('DomainBundle:AccessPointsGroups')
                ->findOneBy([
                    'id' => $apg->getParent()
                ]);

            if (!$parentAccessPointGroup) {
                throw new \Exception("Configurações do access points não foram encontradas");
            }

            $template = $this->getParentTemplateByAccessPointsGroup($parentAccessPointGroup);
        } else {
            return $apg->getTemplate();
        }

        return $template;
    }

    /**
     * @param $entities
     * @return string
     */
    public function getJsonGroupView($entities)
    {
        $arrayGroup = $this->groupAccessPointsToArray($entities);

        $arrayGroupHierarchy = $this->convertToHierarchy($arrayGroup, 'dbid');

        $arrayGroupHierarchyWithoutIds = $this->removeIdsOfHierarchyArray($arrayGroupHierarchy);

        return json_encode($arrayGroupHierarchyWithoutIds);
    }

    /**
     * @param $array
     * @return array
     */
    public function removeIdsOfHierarchyArray(&$array)
    {
        $array = array_values($array);

        foreach ($array as &$item) {
            if (isset($item["children"])) {
                $this->removeIdsOfHierarchyArray($item["children"]);
            }
        }

        return $array;
    }

    /**
     * @param $results
     * @param string $idField
     * @param string $parentIdField
     * @param string $childrenField
     * @return array
     */
    public function convertToHierarchy($results, $idField='id', $parentIdField='parent', $childrenField='children')
    {
        $hierarchy = array();
        $itemReferences = array();
        foreach ( $results as $item ) {
            $id       = $item[$idField];
            $parentId = $item[$parentIdField];
            if (isset($itemReferences[$parentId])) {
                $itemReferences[$parentId][$childrenField][$id] = $item;
                $itemReferences[$id] =& $itemReferences[$parentId][$childrenField][$id];
            } elseif (!$parentId || !isset($hierarchy[$parentId])) {
                $hierarchy[$id] = $item;
                $itemReferences[$id] =& $hierarchy[$id];
            }
        }
        unset($results, $item, $id, $parentId);

        foreach ( $hierarchy as $id => &$item ) {
            $parentId = $item[$parentIdField];
            if ( isset($itemReferences[$parentId] ) ) {
                $itemReferences[$parentId][$childrenField][$id] = $item;
                unset($hierarchy[$id]);
            }
        }
        unset($itemReferences, $id, $item, $parentId);
        return $hierarchy;
    }

    /**
     * @param $array
     * @return array
     */
    public function groupAccessPointsToArray(array $array)
    {
        $result = [];

        foreach($array as $value) {
            $template = 'N/I';

            if ($value->getTemplate()) {
                $template = $value->getTemplate()->getName();
            }

            $result[] = [
                "dbid"                  => $value->getId(),
                "id"                    => $value->getGroupName(),
                "parent"                => $value->getParent(),
                "parent_configurations" => $value->getParentConfigurations(),
                "groupName"             => $value->getGroupName(),
                "is_default"            => $value->getIsDefault(),
                "is_master"             => $value->getIsMaster(),
                'template'              => $template,
                'qtd_access_points'     => count($value->getAccessPoints())
            ];
        }
        return $result;
    }

    /**
     * @param $id
     * @param $parent
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeParent($id, $parent)
    {
        $client = $this->em
            ->getRepository("DomainBundle:Client")
            ->find($this->getLoggedClient())
        ;

        $groupAp = $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findOneBy([
                'client' => $client,
                'id' => (int)$id
            ]);
            
        if (!$groupAp) {
            throw new \Exception("Grupo de ponto de acesso não encontrado.");
        }
        
        if ($parent !== 0) {
            $parentGroupAp = $this->em
                ->getRepository('DomainBundle:AccessPointsGroups')
                ->findOneBy([
                    'client' => $client,
                    'id' => (int)$parent
                ]);

            if (!$parentGroupAp) {
                throw new \Exception("Grupo de ponto de acesso não encontrado.");
            }

            $groupAp->setIsMaster(false);
        } else {
            $parent = 0;
            $groupAp->setParentConfigurations(false);
            $groupAp->setParentTemplate(false);
            $groupAp->setIsMaster(true);
        }

        if (!$groupAp) {
            throw new \Exception("Grupo de ponto de acesso não encontrado.");
        }

        $groupAp->setParent($parent);
        $this->em->persist($groupAp);
        $this->em->flush();
    }

    /**
     * @param Client $client
     * @param Pagination $pagination
     * @param $pagination_array
     * @return mixed
     */
    public function getEntities(Client $client, Pagination $pagination, $pagination_array)
    {
        return $this->accessPointsGroupRepository->getEntitiesByClient( $client, $pagination, $pagination_array );
    }

    /**
     * @param Client $client
     * @return object|AccessPointsGroups[]
     */
    public function getGroupByClient(Client $client)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findBy([
                'client' => $client
            ]);
    }

    /**
     * @param $groupId
     */
    public function setIsMaster($groupId)
    {
        $this->accessPointsGroupRepository->setIsMaster($groupId);
    }
}