<?php

namespace Wideti\DomainBundle\Service\Plan;

use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Repository\PlanRepository;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class PlanServiceImp implements PlanService
{
    use LoggerAware;
    /**
     * @var PlanRepository $planRepository
     */
    private $planRepository;

    public function __construct(PlanRepository $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    function getPlanWithId($id)
    {
        try {
            $plan = $this->planRepository->findPlanById($id);
            return $plan;
        } catch (\Exception $e) {
            $this->logger->addCritical("Não foi possível carregar o plano do usuário com mensagem: ". $e->getMessage().
            "no arquivo ".$e->getFile());
            return null;
        }
    }

    function getPlanShortCode(Plan $plan){
        try  {
            $planCode = $plan->getId();
            $plan = $this->getPlanWithId($planCode);
            return $plan->getShortCode();
        } catch (\Exception $e ) {
            $this->logger->addCritical("Não foi possível carregar o short_code do plano com mensagem: ". $e->getMessage().
                "no arquivo ".$e->getFile().' Na Linha: '. $e->getLine());
            return null;
        }
    }
}

