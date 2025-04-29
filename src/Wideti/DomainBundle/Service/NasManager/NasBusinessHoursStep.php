<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Radcheck;
use Wideti\DomainBundle\Service\BusinessHours\BusinessHoursServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\TimeLimitPolicy;
use Wideti\DomainBundle\Service\Radcheck\RadcheckAware;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class NasBusinessHoursStep implements NasStepInterface
{
    use EntityManagerAware;
    use RadcheckAware;
    use RouterAware;
    use TwigAware;
    use TemplateAware;
    use SessionAware;
    use BusinessHoursServiceAware;
    use ModuleAware;

    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        if ($this->moduleService->checkModuleIsActive('business_hours')) {
            
            $businessHours = $this->businessHoursService->checkAvailable($nas);
            if (!$businessHours->isAvailable()) {
                return $this->render(
                    '@Frontend/General/businessHoursUnavailable.html.twig',
                    [
                        'template' => $this->templateService->templateSettings($this->session->get('campaignId')),
                        'businessHours' => $businessHours
                    ]
                );
            }

	        $apMacAddress = $nas->getAccessPointMacAddress();

	        $accessPoint = $this->em
		        ->getRepository('DomainBundle:AccessPoints')
		        ->getAccessPointByIdentifier($apMacAddress, $client);

	        $accessPointId = ($accessPoint) ? $accessPoint[0]->getId() : null;

	        $businessHours = $this->em
		        ->getRepository('DomainBundle:BusinessHours')
		        ->getByAccessPoint($accessPointId);

	        if (!$businessHours) {
		        $businessHours = $this->em
			        ->getRepository('DomainBundle:BusinessHours')
			        ->getByAllAccessPoints($client);
	        }

	        if ($businessHours) {
				$current_time = strtolower(date("H:i:s"));
		        $intervals = $this->businessHoursService->getHours($businessHours);

				foreach ($intervals as $interval) {
					if ($current_time >= $interval["from"] && $current_time <= $interval["to"]) {
						$radcheck = $this->em
				        ->getRepository('DomainBundle:Radcheck')
				        ->findOneBy(
					        [
						        'client'    => $client,
						        'guest'     => $guest->getMysql(),
						        'attribute' => 'Expiration'
					        ]
				        )
			        ;

			        if ($radcheck == null) {
				        $guestMysql = $this->em
					        ->getRepository('DomainBundle:Guests')
					        ->findOneById($guest->getMysql())
				        ;

				        $radcheck = new Radcheck();
				        $radcheck->setAttribute('Expiration');
				        $radcheck->setOp(':=');
				        $radcheck->setClient($client);
				        $radcheck->setGuest($guestMysql);
                        $radcheck->setApTimezone($this->getApTimezone($guest));
			        }

			        $expiration = date('F j Y H:i:59', strtotime($interval['to']));
			        $radcheck->setValue($expiration);

			        $this->em->persist($radcheck);
			        $this->em->flush();

			        $this->session->set('timelimitModule', TimeLimitPolicy::BUSINESS_HOURS);
					}
				}
	        }
        }
    }

    private function getApTimezone(Guest $guest)
    {
        $apTimezone = TimezoneService::DEFAULT_TIMEZONE;

        if ($guest->getRegistrationMacAddress()) {
            $ap = $this->em->getRepository('DomainBundle:AccessPoints')->findOneBy([
                'identifier' => $guest->getRegistrationMacAddress()
            ]);

            if ($ap) {
                $apTimezone = $ap->getTimezone() ?: $apTimezone;
            }
        }

        return $apTimezone;
    }
}
