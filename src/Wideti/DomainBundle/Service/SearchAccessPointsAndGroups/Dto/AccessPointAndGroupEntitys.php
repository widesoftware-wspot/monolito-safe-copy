<?php

namespace Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto;


use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;

class AccessPointAndGroupEntitys
{
    /**
     * @var AccessPointsGroups[]
     */
    private $groups;

    /**
     * @var AccessPoints[]
     */
    private $accessPoints;

    public function __construct()
    {
        $this->groups = [];
        $this->accessPoints = [];
    }

    /**
     * @return AccessPointsGroups[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param AccessPointsGroups[] $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return AccessPoints[]
     */
    public function getAccessPoints()
    {
        return $this->accessPoints;
    }

    /**
     * @param AccessPoints[] $accessPoints
     */
    public function setAccessPoints($accessPoints)
    {
        $this->accessPoints = $accessPoints;
    }

    /**
     * @return bool
     */
    public function isInAccessPointOrGroup()
    {
        return !empty($this->groups) || !empty($this->accessPoints);
    }
}
