<?php

namespace Wideti\DomainBundle\Service\Plan;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\ClientPlanNotFoundException;
use Wideti\DomainBundle\Exception\NotAuthorizedPlanException;

class PlanAssert
{
    public static function checkOrThrow(Client $client, $requiredPlanShortCode)
    {
        $plan = $client->getPlan();
        if (!$plan) {
            throw new ClientPlanNotFoundException();
        }

        $shortCode = $plan->getShortCode();
        if ($shortCode !== $requiredPlanShortCode) {
            throw new NotAuthorizedPlanException();
        }
    }

    /**
     * @return bool
     */
    public static function isAuthorizedPlan(Client $client, $requiredPlanShortCode)
    {
        $plan = $client->getPlan();
        if (!$plan) {
            throw new ClientPlanNotFoundException();
        }

        $shortCode = $plan->getShortCode();
        if ($shortCode == $requiredPlanShortCode) {
            return true;
        }
        return false;
    }
}
