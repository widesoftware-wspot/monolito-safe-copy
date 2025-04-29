<?php
/**
 * Created by PhpStorm.
 * User: evandro
 * Date: 14/03/19
 * Time: 11:22
 */

namespace Wideti\DomainBundle\Tests\Service\Plan;


use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Exception\ClientPlanNotFoundException;
use Wideti\DomainBundle\Service\Plan\PlanAssert;

class PlanAssertTest extends TestCase
{
    private $clientFail;
    private $clientSuccess;
    private $basicPlan;
    private $proPlan;

    public function setUp()
    {
        $this->basicPlan = new Plan();
        $this->basicPlan->setPlan("Basico");
        $this->basicPlan->setShortCode('basic');

        $this->proPlan = new Plan();
        $this->proPlan->setPlan("PRO");
        $this->proPlan->setShortCode('pro');

        $this->clientFail = new Client();
        $this->clientFail->setPlan(null);

        $this->clientSuccess = new Client();
        $this->clientSuccess->setPlan($this->proPlan);
    }

    public function testMustFailWithoutPlan()
    {
        $this->expectException(ClientPlanNotFoundException::class);
        PlanAssert::isAuthorizedPlan($this->clientFail);
    }

    public function testMustReturnFalseOnWrongClintPlan()
    {
        $plan = $this->clientSuccess->getPlan()->getShortCode();
        $this->assertEquals(false, PlanAssert::isAuthorizedPlan($plan, Plan::BASIC));
    }

    public function testMustReturnTrueOnWrongClintPlan()
    {
        $plan = $this->clientSuccess->getPlan()->getShortCode();
        $this->assertEquals(false, PlanAssert::isAuthorizedPlan($plan, Plan::PRO));
    }
}