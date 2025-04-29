<?php

namespace Wideti\DomainBundle\Service\RadacctReport\Dto;

class GuestUniqueAndRecurringDashboard
{
    private $userNameId;

    /**
     * @return int
     */
    public function getUserNameId()
    {
        return $this->userNameId;
    }

    /**
     * @param int $userNameId
     */
    public function setUserNameId($userNameId)
    {
        $this->userNameId = $userNameId;
    }
}
