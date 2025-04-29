<?php
namespace Wideti\DomainBundle\Service\SearchAccessPointsAndGroups;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto\AccessPointAndGroup;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto\AccessPointAndGroupBuilder;
use Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto\AccessPointAndGroupEntitys;

class SearchAccessPointsAndGroupsImp implements SearchAccessPointsAndGroups
{
    const TYPE_AP = 'ap';
    const TYPE_GROUP = 'group';

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var DocumentManager
     */
    private $documentManager;
    /**
     * @var \Doctrine\ORM\EntityRepository|AccessPointsRepository
     */
    private $apRepository;
    /**
     * @var \Doctrine\ORM\EntityRepository|AccessPointsGroupsRepository
     */
    private $groupRepository;

    public function __construct(EntityManager $entityManager, DocumentManager $documentManager)
    {
        $this->entityManager = $entityManager;
        $this->documentManager = $documentManager;
        $this->apRepository = $entityManager->getRepository('DomainBundle:AccessPoints');
        $this->groupRepository = $entityManager->getRepository('DomainBundle:AccessPointsGroups');
    }

    /**
     * @param string $searchName
     * @param Client $client
     * @return AccessPointAndGroup[]
     */
    public function findAccessPointAndGroups($searchName, Client $client)
    {
        $findAps = $this
            ->apRepository
            ->createQueryBuilder('ap')
            ->where('ap.friendlyName LIKE :searchName')
            ->andWhere('ap.client = :client')
            ->setParameter('searchName', '%' . $searchName . '%')
            ->setParameter('client', $client)
            ->getQuery()
            ->getResult();

        $findGroups = $this
            ->groupRepository
            ->createQueryBuilder('gr')
            ->where('gr.groupName LIKE :searchName')
            ->andWhere('gr.client = :client')
            ->setParameter('searchName', '%' . $searchName . '%')
            ->setParameter('client', $client)
            ->getQuery()
            ->getResult();

        return $this->prepareResult($findAps, $findGroups);
    }

    /**
     * @param $searchName
     * @param Client $client
     * @param $apIds
     * @param $groupIds
     * @return mixed|AccessPointAndGroup[]
     */
    public function findAccessPointAndGroupsNotWithIds($searchName, Client $client, $apIds, $groupIds)
    {
        $qb = $this->entityManager->createQueryBuilder();


        if (!is_null($apIds)) {
            $findAps = $qb->select('ap')
                ->from('DomainBundle:AccessPoints', 'ap')
                ->where('ap.friendlyName LIKE :searchName')
                ->andWhere('ap.client = :client')
                ->andWhere($qb->expr()->notIn('ap.id', $apIds))
                ->setParameter('searchName', '%' . $searchName . '%')
                ->setParameter('client', $client)
                ->getQuery()
                ->getResult();
        } else {
            $findAps = $qb->select('ap')
                ->from('DomainBundle:AccessPoints', 'ap')
                ->where('ap.friendlyName LIKE :searchName')
                ->andWhere('ap.client = :client')
                ->setParameter('searchName', '%' . $searchName . '%')
                ->setParameter('client', $client)
                ->getQuery()
                ->getResult();
        }

        if (!is_null($groupIds)) {
            $findGroups = $qb->select('gr')
                ->from('DomainBundle:AccessPointsGroups', 'gr')
                ->where('gr.groupName LIKE :searchName')
                ->andWhere('gr.client = :client')
                ->andWhere($qb->expr()->notIn('gr.id', $groupIds))
                ->setParameter('searchName', '%' . $searchName . '%')
                ->setParameter('client', $client)
                ->getQuery()
                ->getResult();
        } else {
            $findGroups = $qb->select('gr')
                ->from('DomainBundle:AccessPointsGroups', 'gr')
                ->where('gr.groupName LIKE :searchName')
                ->andWhere('gr.client = :client')
                ->setParameter('searchName', '%' . $searchName . '%')
                ->setParameter('client', $client)
                ->getQuery()
                ->getResult();
        }


        return $this->prepareResult($findAps, $findGroups);
    }

    /**
     * @param AccessPoints[] $accessPoints
     * @param AccessPointsGroups[] $groups
     * @return AccessPointAndGroup[]
     */
    private function prepareResult(array $accessPoints, array $groups)
    {
        $result = [];

        foreach ($accessPoints as $ap) {
            $apBuilder = new AccessPointAndGroupBuilder();
            $ap = $apBuilder
                ->withId($ap->getId())
                ->withName($ap->getFriendlyName())
                ->withType(self::TYPE_AP)
                ->build();
            $result[] = $ap;
        }

        foreach ($groups as $group) {
            $groupBuilder = new AccessPointAndGroupBuilder();
            $group = $groupBuilder
                ->withId($group->getId())
                ->withName($group->getGroupName())
                ->withType(self::TYPE_GROUP)
                ->build();
            $result[] = $group;
        }

        return $this->sortResult($result);
    }

    /**
     * @param AccessPointAndGroup[] $apsAndGroups
     * @return AccessPointAndGroup[]
     */
    private function sortResult(array $apsAndGroups)
    {
        usort($apsAndGroups, function($a, $b) {
            return ($a->getName() < $b->getName()) ? -1 : 1;
        });

        return $apsAndGroups;
    }

    /**
     * @param array $apsAndGroups
     * @return AccessPointAndGroupEntitys
     */
    public function convertToEntity(array $apsAndGroups)
    {
        $entitys = new AccessPointAndGroupEntitys();
        $aps = [];
        $groups = [];

        foreach ($apsAndGroups as $values) {
            $aps = $this->convertApToEntity($values, $aps);
            $groups = $this->convertGroupToEntity($values, $groups);
        }

        $entitys->setAccessPoints($aps);
        $entitys->setGroups($groups);

        return $entitys;
    }

    /**
     * @param $values
     * @param $aps
     * @return array
     */
    private function convertApToEntity($values, $aps)
    {
        if ($values['type'] === self::TYPE_AP) {
            $ap = $this->apRepository->find($values['id']);
            if ($ap) {
                $aps[] = $ap;
            }
        }
        return $aps;
    }

    /**
     * @param $values
     * @param $groups
     * @return array
     */
    private function convertGroupToEntity($values, $groups)
    {
        if ($values['type'] === self::TYPE_GROUP) {
            $group = $this->groupRepository->find($values['id']);
            if ($group) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    /**
     * @param $campaignId
     * @param Client $client
     * @return AccessPointAndGroup[]
     */
    public function findByCampaignId($campaignId, Client $client)
    {
        $campaign = $this
            ->entityManager
            ->getRepository('DomainBundle:Campaign')
            ->findOneBy([
                'id' => $campaignId,
                'client' => $client
            ]);

        $aps = $campaign->getAccessPoints()->getValues();
        $groups = $campaign->getAccessPointsGroups()->getValues();

        return $this->prepareResult($aps, $groups);
    }

    /**
     * @param $guestGroupId
     * @return mixed|AccessPointAndGroup[]
     */
    public function findByGuestGroupId($guestGroupId)
    {
        $guestGroups = $this
            ->documentManager
            ->getRepository('DomainBundle:Group\Group')
            ->findBy(['_id' => $guestGroupId]);

        $apGroupsIds = [];
        $apIds = [];
        foreach ($guestGroups as $apsAndGroup) {
            $groups = $apsAndGroup->getAccessPointGroup();
            $aps = $apsAndGroup->getAccessPoint();
            foreach ($groups as $group) {
                array_push($apGroupsIds, $group->getMysqlId());
            }
            foreach ($aps as $ap) {
                array_push($apIds, $ap->getMysqlId());
            }
        }
        $accessPointGroups = $this
            ->entityManager
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findBy(['id' => $apGroupsIds]);
        $accessPoints = $this
            ->entityManager
            ->getRepository('DomainBundle:AccessPoints')
            ->findBy(['id' => $apIds]);

        return $this->prepareResult($accessPoints, $accessPointGroups);
    }
}
