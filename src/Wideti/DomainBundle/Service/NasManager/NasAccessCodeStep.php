<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Exception;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\AccessCodeDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Radcheck;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeServiceImp;
use Wideti\DomainBundle\Service\ExpirationTime\ExpirationTimeImp;
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
use Wideti\DomainBundle\Helpers\ConvertStringToSecond;

class NasAccessCodeStep implements NasStepInterface
{
    use ModuleAware;
    use SessionAware;
    use EntityManagerAware;
    use TwigAware;
    use RouterAware;
    use TemplateAware;
    use RadcheckAware;
    use RadacctRepositoryAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var AccessCodeServiceImp
     */
    private $accessCodeService;
    private $maxReportLinesPoc;
    /**
     * @var ExpirationTimeImp
     */
    private $expirationTime;
    /**
     * @var EventLoggerManager
     */
    private $logManager;

    /**
     * NasAccessCodeStep constructor.
     * @param FrontendControllerHelper $controllerHelper
     * @param AccessCodeServiceImp $accessCodeService
     * @param $maxReportLinesPoc
     * @param ExpirationTimeImp $expirationTime
     * @param EventLoggerManager $logManager
     */
    public function __construct(
        FrontendControllerHelper $controllerHelper,
        AccessCodeServiceImp $accessCodeService,
        $maxReportLinesPoc,
        ExpirationTimeImp $expirationTime,
        EventLoggerManager $logManager
    ) {
        $this->controllerHelper  = $controllerHelper;
        $this->accessCodeService = $accessCodeService;
        $this->maxReportLinesPoc = $maxReportLinesPoc;
        $this->expirationTime       = $expirationTime;
        $this->logManager        = $logManager;
    }

    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        $isModuleActive = $this->moduleService->checkModuleIsActive('access_code');

        if (!$isModuleActive) {
            return null;
        }

        $accessCodeDto  = $this->session->get('accessCodeDto');
        $freeAccessTime = $this->session->get('freeAccessTime');
        $connectionTime = null;
        $accessCodeType = null;

        $accessCode = $accessCodeDto->getAccessCodeParams()['code'];
        $accessCodeExist = $this->accessCodeService->findByCodeUsed($accessCode);
        if ($freeAccessTime && !array_key_exists('step', $accessCodeDto->getAccessCodeParams())) {
            return $this->freeAccessTime($accessCodeDto, $client, $guest);
        }

        if ($accessCodeDto && $accessCodeDto->isHasAccessCode()) {
            $accessCodeType = ($accessCodeDto) ? $accessCodeDto->getAccessCodeParams()['type'] : null;
            $connectionTime = ($accessCodeDto) ? $accessCodeDto->getAccessCodeParams()['connectionTime'] : null;
        }
        if ($connectionTime) {
            $numberOfAccts = $this->radacctRepository->getOnlineAccountingsByUsername([
                'client'    => $client->getId(),
                'username'  => $guest->getMysql()
            ]);

            if ($numberOfAccts > 0) {
                return $this->render(
                    'FrontendBundle:General:multipleDevice.html.twig',
                    [
                        'guest'    => $guest,
                        'template' => $this->templateService->templateSettings(
                            $this->session->get('campaignId')
                        )
                    ]
                );
            }
            $this->setExpiration($accessCodeDto, $client, $guest);
        } else {
            $this->removeExpiration($client, $guest);
        }
        if ($accessCodeType == AccessCode::TYPE_RANDOM && $accessCodeExist == null) {
            $this->accessCodeService->setAccessCodeAsUsed(
                $guest,
                [
                    'code'           => $accessCodeDto->getAccessCodeParams()['code'],
                    'connectionTime' => $accessCodeDto->getAccessCodeParams()['connectionTime']
                ]
            );
        }
    }

	/**
	 * @throws Exception
	 */
	private function freeAccessTime(AccessCodeDto $accessCodeDto, Client $client, Guest $guest)
    {
        $freeAccessTime     = $accessCodeDto->getFreeAccessParams()['freeAccessTime'];
        $endPeriodText      = $accessCodeDto->getFreeAccessParams()['endPeriodText'];

        // Carrega Radcheck do banco
        $radcheckTimeLeft = $this->em
            ->getRepository('DomainBundle:Radcheck')
            ->findOneBy(
                [
                    'client'    => $client,
                    'guest'     => $guest->getMysql(),
                    'attribute' => 'Expiration'
                ]
            )
        ;

        if ($radcheckTimeLeft == null) {
        	$guestMysql = $this->em
                ->getRepository('DomainBundle:Guests')
                ->findOneBy([
                    'id' => $guest->getMysql()
                ])
            ;

            $ap = $this->getApTimezone($guest);
            $apTimezone = $ap ? $ap : TimezoneService::DEFAULT_TIMEZONE;

            $radcheckTimeLeftEntity = $this->expirationTime->get($client, $guest);
            $radcheckTimeLeft       = $radcheckTimeLeftEntity ? $radcheckTimeLeftEntity->getFormatted() : null;
            $expirationTime         = Carbon::createFromTimeString(
                date('Y-m-d H:i:s', strtotime(str_replace('/', '-',
                    $this->radcheckService->convertTime($freeAccessTime)
                )))
            );

            $expirationTime->setTimezone($apTimezone);

            $utcTimezone = $expirationTime->copy();
            $utcTimezone->setTimezone(TimezoneService::UTC);

            if ($radcheckTimeLeft == null) {
                $this->expirationTime->create($client, $guestMysql, $utcTimezone, $apTimezone);
            } else {
                $this->expirationTime->update(
                    $client,
                    $guestMysql,
                    $radcheckTimeLeftEntity,
                    $utcTimezone,
                    $apTimezone
                );
            }

        } else {
			$now            = new DateTime('now', new DateTimeZone("UTC"));
			$expirationTime = new DateTime($radcheckTimeLeft->getValue(), new DateTimeZone("UTC"));
			$nextAllowedLogin = $this->getNextLoginDatetime($radcheckTimeLeft, $accessCodeDto);

			// Checa se visitante esta dentro do tempo de bloqueio ainda
			$isBlocked = function() use($now, $expirationTime, $nextAllowedLogin) {
				return ($now > $expirationTime) && ($now < $nextAllowedLogin);
			};

			// Checa se usuário já passou o tempo de bloqueio e esta apto a um novo periodo
			$hasNewFreeAccess = function() use($now, $expirationTime, $nextAllowedLogin) {
				return ($now > $expirationTime) && ($now > $nextAllowedLogin);
			};

            if ($isBlocked()) {
                $this->session->set('freeAccess', false);

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($client)
                    ->withEventIdentifier(EventIdentifier::BLOCK_BY_TIME)
                    ->withEventType(EventType::BLOCK_BY_TIME)
                    ->withRequest(null)
                    ->withGuest($guest)
                    ->withNas($this->session->get(Nas::NAS_SESSION_KEY))
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);

				$ap = $this->getApTimezone($guest);
				$apTimezone = $ap ?: TimezoneService::DEFAULT_TIMEZONE;

                return $this->render(
                    'FrontendBundle:SignIn:signInBlockPerTime.html.twig',
                    [
                        'period'   => $nextAllowedLogin->setTimezone(new DateTimeZone($apTimezone))->format('d/m/Y à\\s H:i:s'),
                        'guest'    => $guest,
                        'template' => $this->templateService->templateSettings(
                            $this->session->get('campaignId')
                        ),
                        'customMessage' => $endPeriodText
                    ]
                );

            } else if ($hasNewFreeAccess()) {

				$expirationTime = $now->add(
					new \DateInterval(
						ConvertStringToSecond::convertPeriod($freeAccessTime)
					)
				);

				$expirationTime->setTimezone(new DateTimeZone(TimezoneService::UTC));
				$radcheckTimeLeft->setValue($expirationTime->format('F j Y H:i:s'));

            }
        }

	    $this->session->set('timelimitModule', TimeLimitPolicy::ACCESS_CODE);
    }

	/**
	 * @return DateTime
	 * @throws Exception
	 */
    private function getNextLoginDatetime(Radcheck $radcheck, AccessCodeDto $accessCodeDto)
	{
		$freeAccessTime     = $accessCodeDto->getFreeAccessParams()['freeAccessTime'];
		$freeAccessPeriod   = $accessCodeDto->getFreeAccessParams()['freeAccessPeriod'];

    	$expirationTime = new DateTime($radcheck->getValue(), new DateTimeZone("UTC"));

    	$period = $expirationTime->add(new \DateInterval(ConvertStringToSecond::convertPeriod($freeAccessPeriod)));
		return $period->sub(new \DateInterval(ConvertStringToSecond::convertPeriod($freeAccessTime)));
	}

    private function setExpiration(AccessCodeDto $accessCodeDto, Client $client, Guest $guest)
    {
        $radcheck = $this->em
            ->getRepository('DomainBundle:Radcheck')
            ->findOneBy(
                [
                    'client'    => $client,
                    'guest'     => $guest->getMysql(),
                    'attribute' => 'Expiration'
                ]
            );
        
        if ($radcheck == null) {
            $this->createNewExpiration($guest, $accessCodeDto, $client);
            $this->session->set('timelimitModule', TimeLimitPolicy::ACCESS_CODE);
        }

        if (!$accessCodeDto->getAccessCodeParams()['used']) {
            $this->removeExpiration($client, $guest);
            $this->createNewExpiration($guest, $accessCodeDto, $client);
        }
    }

    private function createNewExpiration(Guest $guest, AccessCodeDto $accessCodeDto, Client $client)
    {
        $guestMysql = $this->em
            ->getRepository('DomainBundle:Guests')
            ->findOneBy([
                'id' => $guest->getMysql()
            ]);

        $connectionTime = $accessCodeDto->getAccessCodeParams()['connectionTime'];

        $ap = $this->getApTimezone($guest);
        $apTimezone = $ap ? $ap : TimezoneService::DEFAULT_TIMEZONE;

        $radcheckTimeLeftEntity = $this->expirationTime->get($client, $guest);
        $radcheckTimeLeft       = $radcheckTimeLeftEntity ? $radcheckTimeLeftEntity->getFormatted() : null;
        $code                   = $accessCodeDto->getAccessCodeParams()['code'];

        $expirationTime = Carbon::createFromTimeString(
            date('Y-m-d H:i:s', strtotime(str_replace('/', '-',
                $this->radcheckService->convertTime($connectionTime)
            )))
        );

        $expirationTime->setTimezone($apTimezone);
        $utcTimezone = $expirationTime;
        $utcTimezone->setTimezone(TimezoneService::UTC);
        if ($radcheckTimeLeft == null) {
            $radcheckExpiration     = $this->accessCodeService->getRadcheckExpirationByCode($code);
            if ($radcheckExpiration) {
                $radcheckExpiration = $radcheckExpiration['value'];
            }
            $this->expirationTime->create($client, $guestMysql, $expirationTime, $apTimezone, null, $radcheckExpiration);
        } else {
            $this->expirationTime->update(
                $client,
                $guestMysql,
                $radcheckTimeLeftEntity,
                $expirationTime,
                $apTimezone
            );
        }

        $this->session->set('timelimitModule', TimeLimitPolicy::ACCESS_CODE);
    }

    private function removeExpiration(Client $client, Guest $guest)
    {
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

        if ($radcheck) {
            $this->em->remove($radcheck);
            $this->em->flush();
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
