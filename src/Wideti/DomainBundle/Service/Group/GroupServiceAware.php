<?php

namespace Wideti\DomainBundle\Service\Group;

/**
 * To use this, do:
 * - [ setGroupService, ["@core.service.group"]]
 */
trait GroupServiceAware
{
    /**
     * @var GroupService $groupService
     */
    protected $groupService;

    public function setGroupService(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }
}
