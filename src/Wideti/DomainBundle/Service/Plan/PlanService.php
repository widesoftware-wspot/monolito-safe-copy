<?php

namespace Wideti\DomainBundle\Service\Plan;

use Wideti\DomainBundle\Entity\Plan;

interface PlanService
{
    /**
     * @param $id
     * @return Plan
     */
	public function getPlanWithId($id);
    function getPlanShortCode(Plan $plan);
}