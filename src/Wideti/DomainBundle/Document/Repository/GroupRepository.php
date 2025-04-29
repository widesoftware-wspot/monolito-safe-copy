<?php

namespace Wideti\DomainBundle\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;

class GroupRepository extends DocumentRepository
{
    public function getGroupsToForm()
    {
        $results = $this->createQueryBuilder()
            ->select()
            ->sort(['default' => -1])
        ;

        $results = $results->getQuery();

        $groups = [];
        foreach ($results->toArray() as $group) {
            $groups[$group->getShortcode()] = $group->getName();
        }

        return $groups;
    }

    public function getGroupsToId()
    {
        $results = $this->createQueryBuilder()
            ->select()
            ->sort(['default' => -1])
        ;

        $results = $results->getQuery();

        $groups = [];
        foreach ($results->toArray() as $group) {
            $groups[$group->getId()] = $group->getName();
        }

        return $groups;
    }

    public function findOneByShortcode($shortcode = null)
    {
        if (!$shortcode) {
            $shortcode = Group::GROUP_DEFAULT;
        }

        return $this->createQueryBuilder()
            ->select()
            ->field('shortcode')->equals($shortcode)
            ->getQuery()
            ->getSingleResult();
    }

    public function findOneBandwidth()
    {
        return $this->createQueryBuilder()
            ->select()
            ->field('configurations')
            ->getQuery()
            ->getSingleResult();
    }
}
