<?php

namespace Wideti\DomainBundle\Service\PolicyWriter;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ExpirationTime\ExpirationTimeImp;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicyBuilder;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\TimeLimitPolicy;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\FrontendBundle\Factory\Nas;

class TimeLimitPolicyWriter implements PolicyWriter
{
    /**
     * @var EntityManager
     */
    private $entityManager;
	/**
	 * @var Session
	 */
	private $session;
    /**
     * @var ExpirationTimeImp
     */
    private $expirationTime;

    /**
     * TimeLimitPolicyWriter constructor.
     * @param EntityManager $entityManager
     * @param Session $session
     * @param ExpirationTimeImp $expirationTime
     */
	public function __construct(
	    EntityManager $entityManager,
        Session $session,
        ExpirationTimeImp $expirationTime
    ) {
        $this->entityManager    = $entityManager;
	    $this->session          = $session;
        $this->expirationTime   = $expirationTime;
    }

    /**
     * @param Nas $nas
     * @param Guest $guest
     * @param Client $client
     * @param RadiusPolicyBuilder $builder
     * @throws \Exception
     */
    public function write(Nas $nas, Guest $guest, Client $client, RadiusPolicyBuilder $builder)
    {
        $radcheck = $this->expirationTime->get($client, $guest);
        $activeBlockTime = $this->session->get('timelimitModule');

        if ($radcheck) {
            //TODO -> esse IF é provisório pois refatorei apenas o módulo de Grupo de Visitantes para gerar o Expiration
            //TODO -> em UTC. Assim que todos os outros módulos forem refatorados, removeremos esse IF.
            //TODO -> métodos que não foram refatorados:
            //TODO -> --- NasBusinessHoursStep::process()
            //TODO -> --- RadcheckService::setExpirationTime()
            //TODO -> ------ Essa classe RadcheckService deverá ser removida, dando lugar a ExpirationTimeImp().
            if ($radcheck->getApTimezone()) {
                $dateNow = Carbon::now(TimezoneService::UTC);
                $expirationDate = Carbon::createFromTimeString($radcheck->getValue(), TimezoneService::UTC);
                if (
                    $activeBlockTime &&
                    ($activeBlockTime == TimeLimitPolicy::EMAIL_CONFIRMATION || $activeBlockTime == TimeLimitPolicy::BUSINESS_HOURS)
                ) {
                    $dateNow = Carbon::now($radcheck->getApTimezone());
                    $expirationDate = Carbon::createFromTimeString($radcheck->getValue(), $radcheck->getApTimezone());
                }
            } else {
                $dateNow = new \DateTime();
                $expirationDate = Carbon::createFromTimeString($radcheck->getValue());
            }

            $timeNow        = $dateNow->getTimestamp();
            $timeExpiration = $expirationDate->getTimestamp();

            $accessTime     = $timeExpiration - $timeNow;
            $builder->withTimeLimitPolicy(
	            $this->session->get('timelimitModule') ?: TimeLimitPolicy::NOT_INFORMED,
                ($accessTime > 0) ? $accessTime : 0,
                true
            );
        }
    }
}
