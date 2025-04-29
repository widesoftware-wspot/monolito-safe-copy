<?php

namespace Wideti\DomainBundle\Service\Group;

use Wideti\DomainBundle\Document\Group\AccessPoint;
use Wideti\DomainBundle\Document\Group\AccessPointGroup;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\ConfigurationValue;
use Wideti\DomainBundle\Document\Group\Factory\FactoryDefaultGroupConfigurations;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;

class GroupService
{
    const DEFAULT_GUEST_GROUP = 'guest';

    use MongoAware;
    use PaginatorAware;
    use LoggerAware;
    use EntityManagerAware;

    private $accessPointsRepository;

    public function __construct(
        AccessPointsRepository $accessPointsRepository
    )
    {
        $this->accessPointsRepository = $accessPointsRepository;
    }

    public function getAllGroups($data = [])
    {
        $repository = $this->mongo->getRepository('DomainBundle:Group\Group');
        if (!empty($data['name'])) {
            return $repository->findBy(
                [
                    'name' => new \MongoRegex('/'. $data['name'] .'/i')
                ]
            );
        }

        return $repository->findBy(
            [],
            ['default' => -1,"name" => 1 ]
        );
    }

    public function create(Group $group)
    {
        $this->mongo->persist($group);
        if (!$group->getDefault()) {
            $group->setShortcode("custom_" . $group->getId());
        }
        $this->mongo->flush();
    }

    public function getGroupByShortCode($shortCode)
    {
    	$group = $this->mongo->getRepository('DomainBundle:Group\Group')
		    ->findOneBy([
		    	'shortcode' => $shortCode
		    ]);
    	return $group;
    }

    public function getAllDefaultConfigurations()
    {
        $blockPerTimeConfig = FactoryDefaultGroupConfigurations::get(FactoryDefaultGroupConfigurations::BLOCK_PER_TIME);
        $validityAccess = FactoryDefaultGroupConfigurations::get(FactoryDefaultGroupConfigurations::VALIDITY_ACCESS);
        $bandwidth = FactoryDefaultGroupConfigurations::get(FactoryDefaultGroupConfigurations::BANDWIDTH_LIMIT);
        return [$blockPerTimeConfig, $validityAccess, $bandwidth];
    }

    public function prepareGroupToSave($formData = [], $apsIds, $apGroupsIds)
    {
        if (empty($formData)) {
            return false;
        }

        /**
         * @var Group $group
         */
        $group          = $formData['entity'];
        $configurations = $formData['configurations'];


        if (!$group->getDefault()) {
            $group->setName($formData['name']);
        }

        $group->setConfigurations([]);

        /**
         * @var Configuration $config
         */
        foreach ($configurations as $config) {
            if ($config->getShortcode() === 'block_per_time') {
                $configEnable   = $config->getConfigurationValueByKey('enable_block_per_time');
                $configTime     = $config->getConfigurationValueByKey('block_per_time_time');
                $configPeriod   = $config->getConfigurationValueByKey('block_per_time_period');

                if ($formData['enable_block_per_time']) {
                    $configTime->setValue($formData['block_per_time_time']);
                    $configPeriod->setValue($formData['block_per_time_period']);
                    $configEnable->setValue($formData['enable_block_per_time']);
                } else {
                    $configEnable->setValue(false);
                    $configTime->setValue("");
                    $configPeriod->setValue("");
                }
            }

            if ($config->getShortcode() === 'validity_access') {
                $configEnable   = $config->getConfigurationValueByKey('enable_validity_access');
                $configDate     = $config->getConfigurationValueByKey('validity_access_date_limit');

                if ($formData['enable_validity_access']) {
                    $configEnable->setValue($formData['enable_validity_access']);
                    $configDate->setValue($formData['validity_access_date_limit']);
                } else {
                    $configEnable->setValue(false);
                    $configDate->setValue("");
                }
            }

            if ($config->getShortcode() === "bandwidth") {
                $configEnable   = $config->getConfigurationValueByKey('enable_bandwidth');
                $configDownload = $config->getConfigurationValueByKey('bandwidth_download_limit');
                $configUpload   = $config->getConfigurationValueByKey('bandwidth_upload_limit');

                if ($formData['enable_bandwidth']) {
                    $configEnable->setValue($formData['enable_bandwidth']);
                    $configDownload->setValue($formData['bandwidth_download_limit']);
                    $configUpload->setValue($formData['bandwidth_upload_limit']);
                } else {
                    $configEnable->setValue(false);
                    $configDownload->setValue("");
                    $configUpload->setValue("");
                }
            }
            $group->addConfiguration($config);
        }

        $this->purgeAcessPointAndAccessPointGroups($group);
        if (!empty($apsIds) || !empty($apGroupsIds) ) {
            $group->setInAccessPoints(true);
            foreach ($apsIds as $apsId) {
                $newAp = new AccessPoint();
                $newAp->setMysqlId($apsId);
                $group->addAccessPoint($newAp);
            }
            foreach ($apGroupsIds as $apGroupsId) {
                $newGroupId = new AccessPointGroup();
                $newGroupId->setMysqlId($apGroupsId);
                $group->addAccessPointGroup($newGroupId);
            }
        } else {
            $group->setInAccessPoints(false);
        }

        return $group;
    }

    public function getAllIdsFromApsInGuestGroups()
    {
        $apsAndGroups =  $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findAll();
        $apIds = [];
        foreach ($apsAndGroups as $apsAndGroup) {
            $aps = $apsAndGroup->getAccessPoint();
            foreach ($aps as $ap) {
                array_push($apIds, $ap->getMysqlId());
            }
        }
        return $apIds;
    }

    public function getAllIdsFromGroupInGuestGroups()
    {
        $apsAndGroups =  $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findAll();
        $groupIds = [];
        foreach ($apsAndGroups as $apsAndGroup) {
            $groups = $apsAndGroup->getAccessPointGroup();
            foreach ($groups as $group) {
                array_push($groupIds, $group->getMysqlId());
            }
        }
        return $groupIds;
    }

    public function getApsFromGroupIds($ids)
    {
        $groups = $this->em->getRepository('DomainBundle:AccessPoints')->findBy(['group' => $ids]);
        $apIds = [];
        foreach ($groups as $group) {
            $id = $group->getId();
            array_push($apIds, $id);
        }
        return $apIds;
    }

    public function getGuestsByGroupPaginated(Group $group, $page = 1, $legalKindKey, $offset = 20, $filterField = null, $filterValue = null)
    {
        $page = $page ? $page : 1;

        $guestRepository = $this->mongo->getRepository('DomainBundle:Guest\Guest');

        $qb = $guestRepository->createQueryBuilder();

        if (!empty($filterField) && $filterField != "all") {
                $fieldPath = 'properties.' . $filterField;
                $field = $this->mongo
                ->getRepository('DomainBundle:CustomFields\Field')
                ->findOneBy(['identifier' => $filterField]);

                if ($field->getType() == "choice" || $field->getType() == "multiple_choice") {
                    if ($filterValue == "none") {
                        $qb->addAnd(
                            $qb->expr()->addOr(
                                $qb->expr()->field($fieldPath)->equals(""),
                                $qb->expr()->field($fieldPath)->exists(false)
                            )
                        );
                    } else if ($filterValue == "all") {
                        $qb->addAnd(
                            $qb->expr()->addAnd(
                                $qb->expr()->field($fieldPath)->notEqual(""),
                                $qb->expr()->field($fieldPath)->exists(true)
                            )
                        );
                    } else {
                        $choices = $field->getChoices()["pt_br"];
                        $position = 0;
                        foreach ($choices as $key => $choice) {
                            if ($key == $filterValue) {
                                break;
                            }
                            $position += 1;
                        }
                        $choicePt = array_values($choices)[$position];
                        $choiceEn = array_values($field->getChoices()["en"])[$position];
                        $choiceEs = array_values($field->getChoices()["es"])[$position];
                        if ($field->getType() == "multiple_choice") {
                            $choicePt =  new \MongoRegex("/". $choicePt ."/");
                            $choiceEn = new \MongoRegex("/". $choiceEn ."/");
                            $choiceEs = new \MongoRegex("/". $choiceEs ."/");
                        }
                        $qb->addAnd(
                            $qb->expr()->addOr(
                                $qb->expr()->field($fieldPath)->equals($choicePt),
                                $qb->expr()->field($fieldPath)->equals($choiceEn),
                                $qb->expr()->field($fieldPath)->equals($choiceEs)
                            )
                        );
                    }
                } else {
                    $qb->addAnd(
                        $qb->expr()->addOr(
                            $qb->expr()->field($fieldPath)->equals(new \MongoRegex("/.*".$filterValue.".*/i"))
                        )
                    );
                }
        }

        if ($group->getShortcode() === "guest") {
            $qb->addAnd(
                $qb->expr()->addOr(
                    $qb->expr()->field('group')->equals($group->getShortcode()),
                    $qb->expr()->field('group')->exists(false)
                )
            );
        } else {
            $qb->addAnd(
                $qb->expr()->addOr($qb->expr()->field('group')->equals($group->getShortcode()))
            );
        }

        if ($legalKindKey == LegalKinds::TERMO_CONSENTIMENTO) {
            $qb->field('hasConsentRevoke')->notEqual(true);
        }

        $guests = $qb->getQuery();

        return $this->paginator->paginate($guests, $page, $offset);
    }

    public function getGuestsByGroup(Group $group)
    {
        $guestRepository = $this->mongo->getRepository('DomainBundle:Guest\Guest');
        return $guestRepository->findBy([
            'group' => $group->getShortcode()
        ]);
    }

    public function remove(Group $group, Client $client)
    {
        if ($group->getDefault()) {
            return false;
        }

        $this->moveGuestsToGroup($group->getShortcode(), Group::GROUP_DEFAULT, $client->getDomain());
        $this->mongo->remove($group);
        $this->mongo->flush();
        return true;
    }

    /**
     * @param $fromGroupShortCode
     * @param $toGroupShortCode
     * @param $clientDomain
     * @return bool Este método envia para o workers, onde será executado o script para mover o grupo dos visitantes.
     *
     * Este método envia para o workers, onde será executado o script para mover o grupo dos visitantes.
     */
    private function moveGuestsToGroup($fromGroupShortCode, $toGroupShortCode, $clientDomain)
    {
        if (empty($clientDomain)) {
            $this->logger->addCritical('No domain when try to move guests between groups.');
            return false;
        }

        $databasename = StringHelper::slugDomain($clientDomain);
        $collection = $this
            ->mongo
            ->getConnection()
            ->getMongoClient()
            ->selectDB($databasename)
            ->selectCollection('guests');

        $query = [
            'group' => $fromGroupShortCode
        ];

        $update = [
            '$set' => [
                'group' => $toGroupShortCode
            ]
        ];

        $options = [
            'multiple' => true
        ];

        $collection->update($query, $update, $options);
        return true;
    }

    public function moduleIsActive($group, $config)
    {
        $configItem = '';

        switch ($config) {
            case Configuration::BLOCK_PER_TIME:
                $configItem = ConfigurationValue::BLOCK_PER_TIME;
                break;
            case Configuration::VALIDITY_ACCESS:
                $configItem = ConfigurationValue::VALIDITY_ACCESS;
                break;
            case Configuration::BANDWIDTH:
                $configItem = ConfigurationValue::BANDWIDTH;
                break;
        }

        foreach ($group->getConfigurations() as $item) {
            if ($item->getShortcode() == $config) {
                $value = $item->getConfigurationValueByKey($configItem);
                if ($value) {
                    return boolval($value->getValue());
                }
            }
        }

        return false;
    }

    public function getConfigurationValue($group, $config, $key)
    {
        foreach ($group->getConfigurations() as $item) {
            if ($item->getShortcode() == $config) {
                $value = $item->getConfigurationValueByKey($key);

                if ($value) {
                    return $value->getValue();
                }
            }
        }

        return null;
    }

    public function checkModuleIsActive($module)
    {
        if ($module == 'blockPerTimeOrAccessValidity') {
            $groups = $this->mongo->getRepository('DomainBundle:Group\Group')->findAll();

            foreach ($groups as $group) {
                $blockPertime   = $this->moduleIsActive($group, Configuration::BLOCK_PER_TIME);
                $validityAccess = $this->moduleIsActive($group, Configuration::VALIDITY_ACCESS);

                if ($blockPertime || $validityAccess) {
                    return true;
                }
            }
        }

        return false;
    }

    public function sendGuestTo($groupShortcode, $guestsIds = [])
    {
        $guestRepo = $this->mongo->getRepository('DomainBundle:Guest\Guest');

        foreach ($guestsIds as $guestId) {
            $guest = $guestRepo->findOneBy([
                'id' => $guestId
            ]);

            $guestGroup = $guest->getGroup() ?: 'guest';

            $this->removeExpiration($guestGroup, $guest);

            $guest->setGroup($groupShortcode);
            $this->mongo->merge($guest);
            $this->mongo->flush();
        }
    }

    private function removeExpiration($fromGroupShortCode, Guest $guest)
    {
        $group = $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findOneBy([
                'shortcode' => $fromGroupShortCode
            ]);

        $blockPerTimeStatus = $this->moduleIsActive($group, Configuration::BLOCK_PER_TIME);
        $validityAccessStatus = $this->moduleIsActive($group, Configuration::VALIDITY_ACCESS);

        if ($blockPerTimeStatus || $validityAccessStatus) {
            $guestMysql = $this->em->getRepository('DomainBundle:Guests')
                ->findOneBy(['id' => $guest->getMysql()]);

            $radcheck = $this->em->getRepository('DomainBundle:Radcheck')
                ->findOneBy([
                    'guest' => $guestMysql,
                    'attribute' => 'Expiration'
                ]);

            if ($radcheck) {
                $this->em->remove($radcheck);
                $this->em->flush();
            }
        }
    }

    public function getGroupByAccessPointIds($ids)
    {
        return $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findOneBy([
                'accessPoint.mysqlId' => $ids
            ]);
    }

    public function getGuestGroupFromNas(Nas $nas)
    {
        $guestGroup = null;

        $methods = [
            'getGroupIfExistsFromAccessPoint',
            'getGroupIfExistsFromAccessPointGroup'
        ];

        foreach ($methods as $method) {
            if ($guestGroup) {
               break;
            }

            $guestGroup = $this->$method($nas);

        }

        return ($guestGroup == null) ? self::DEFAULT_GUEST_GROUP : $guestGroup;
    }

    public function getGroupIfExistsFromAccessPoint(Nas $nas)
    {
        $accessPoint = $this->getAccessPointData($nas);

        if ($accessPoint) {
            return $this->getGroupFromNAS('accessPoint', $accessPoint->getId());
        }

        return null;
    }

    public function getGroupIfExistsFromAccessPointGroup(Nas $nas)
    {
        $accessPoint = $this->getAccessPointData($nas);

        if ($accessPoint) {
            $apGroup = $accessPoint->getGroup();

            if ($apGroup) {
                return $this->getGroupFromNAS('accessPointGroup', $apGroup->getId());
            }
        }

        return null;
    }

    /**
     * @param Nas $nas
     * @return AccessPoints
     */
    public function getAccessPointData(Nas $nas)
    {
        return $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([ 'identifier' => $nas->getAccessPointMacAddress() ]);
    }

    public function getGroupFromNAS($accessPointField, $accessPointData)
    {
        $group = $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findOneBy([ "inAccessPoints" => true, "{$accessPointField}.mysqlId" => $accessPointData]);

        if ($group) {
            return $group->getShortcode();
        }

        return null;
    }

    public function purgeAcessPointAndAccessPointGroups($group)
    {
        $aps = $group->getAccessPoint();
        $grps = $group->getAccessPointGroup();
        foreach ($aps as $ap) {
            $this->mongo->remove($ap);
            $this->mongo->flush();
        }
        foreach ($grps as $grp) {
            $this->mongo->remove($grp);
            $this->mongo->flush();
        }
    }

    public function getGroupShortcodeById($groupId)
    {
        $group = $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findOneBy(['id' => $groupId]);

        if ($group !== null) {
            return $group->getShortcode();
        }
        return null;
    }
}
