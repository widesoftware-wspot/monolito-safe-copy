<?php
namespace Wideti\DomainBundle\Service\NasManager;

use Carbon\Carbon;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\ExpirationTime\ExpirationTimeImp;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\TimeLimitPolicy;
use Wideti\DomainBundle\Service\Radcheck\RadcheckAware;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\ConvertStringToSecond;

class NasGuestGroupStep implements NasStepInterface
{
    use EntityManagerAware;
    use MongoAware;
    use RadcheckAware;
    use RouterAware;
    use TwigAware;
    use TemplateAware;
    use SessionAware;
    use RadacctRepositoryAware;
    use GroupServiceAware;
    use LoggerAware;

    private $maxReportLinesPoc;
    /**
     * @var AccessPointsService
     */
    private $accessPointsService;
    /**
     * @var ExpirationTimeImp
     */
    private $expirationTime;
    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var EventLoggerManager
     */
    private $logManager;

    /**
     * NasGuestGroupStep constructor.
     * @param $maxReportLinesPoc
     * @param AccessPointsService $accessPointsService
     * @param ExpirationTimeImp $expirationTime
     * @param FrontendControllerHelper $controllerHelper
     * @param EventLoggerManager $logManager
     */
    public function __construct(
        $maxReportLinesPoc,
        AccessPointsService $accessPointsService,
        ExpirationTimeImp $expirationTime,
        FrontendControllerHelper $controllerHelper,
        EventLoggerManager $logManager
    ) {
        $this->maxReportLinesPoc    = $maxReportLinesPoc;
        $this->accessPointsService  = $accessPointsService;
        $this->expirationTime       = $expirationTime;
        $this->controllerHelper     = $controllerHelper;
        $this->logManager           = $logManager;
    }

    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        $group          = $this->mongo
            ->getRepository('DomainBundle:Group\Group')
            ->findOneByShortcode($guest->getGroup());
        $blockPerTime   = $this->groupService->moduleIsActive($group, Configuration::BLOCK_PER_TIME);
        $validityAccess = $this->groupService->moduleIsActive($group, Configuration::VALIDITY_ACCESS);
        $guestMysql     = $this->em->getRepository('DomainBundle:Guests')->findOneBy(['id' => $guest->getMysql()]);
        $ap             = $this->accessPointsService->getAccessPointByIdentifier($nas->getAccessPointMacAddress());
        $apTimezone     = $ap ? $ap->getTimezone() : TimezoneService::DEFAULT_TIMEZONE;

        if (!$guestMysql) {
            $this->logger->addCritical("Guest MySQL not found on NasGuestGroupStep", [
                "client" => $client->getDomain(),
                "guestMysql" => $guest->getMysql(),
                "hasAtMySQL" => $guestMysql
            ]);

            return $this->controllerHelper->redirectToRoute("frontend_index");
        }


        if ($validityAccess && $guest->getStatus() == Guest::STATUS_ACTIVE) {
            $time = $this->groupService->getConfigurationValue(
                $group,
                Configuration::VALIDITY_ACCESS,
                'validity_access_date_limit'
            );


            $ap = $this->accessPointsService->getAccessPointByIdentifier($nas->getAccessPointMacAddress());
            $apTimezone = $ap ? $ap->getTimezone() : TimezoneService::DEFAULT_TIMEZONE;

            $timeFromAPThatIsBeingAccessed    = Carbon::createFromFormat('d/m/Y H:i', $time, $apTimezone);
            $timeNowFromAPThatIsBeingAccessed = Carbon::now(new \DateTimeZone($apTimezone));
            if ($timeFromAPThatIsBeingAccessed->lt($timeNowFromAPThatIsBeingAccessed)) {

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($client)
                    ->withEventIdentifier(EventIdentifier::VALIDITY_ACCESS_HAS_EXPIRED)
                    ->withEventType(EventType::VALIDITY_ACCESS_HAS_EXPIRED)
                    ->withRequest(null)
                    ->withGuest($guest)
                    ->withNas($nas)
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);

                return $this->render(
                    'FrontendBundle:SignIn:signInValidityAccessHasExpired.html.twig',
                    [
                        'guest'     => $guest,
                        'template'  => $this->templateService->templateSettings($this->session->get('campaignId'))
                    ]
                );
            }

            $radcheckTimeLeftEntity = $this->expirationTime->get($client, $guest);
            $radcheckTimeLeft       = $radcheckTimeLeftEntity ? $radcheckTimeLeftEntity->getFormatted() : null;
            $expirationTime         = Carbon::createFromTimeString(
                date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $time))),
                $apTimezone
            );

            if ($radcheckTimeLeft == null) {
                $this->expirationTime->create($client, $guestMysql, $expirationTime, $apTimezone, $group->getId());
            } else {
                $this->expirationTime->update(
                    $client,
                    $guestMysql,
                    $radcheckTimeLeftEntity,
                    $expirationTime,
                    $apTimezone,
                    $group->getId()
                );
            }

	        $this->session->set('timelimitModule', TimeLimitPolicy::VALIDITY_ACCESS);
        }
        if ($blockPerTime && $guest->getStatus() == Guest::STATUS_ACTIVE) {
            $time = $this->groupService->getConfigurationValue(
                $group,
                Configuration::BLOCK_PER_TIME,
                'block_per_time_time'
            );


            $period = $this->groupService->getConfigurationValue(
                $group,
                Configuration::BLOCK_PER_TIME,
                'block_per_time_period'
            );

            $radcheckTimeLeftEntity = $this->expirationTime->get($client, $guest);

            $radcheckTimeLeft = $radcheckTimeLeftEntity ? $radcheckTimeLeftEntity->getFormatted() : null;
            $now = Carbon::now(TimezoneService::UTC);

            if ($radcheckTimeLeft == null) {
                $expirationTime = $now->add(
                    new \DateInterval(
                        ConvertStringToSecond::convertPeriod($time)
                    )
                );
                $this->expirationTime->create($client, $guestMysql, $expirationTime, $apTimezone, $group->getId());
            } else {
                $radcheckTimeLeftAux = clone $radcheckTimeLeft;

                if ($radcheckTimeLeft->gt($now)) {
                    return false;
                }

                $blockTime = $now->add(new \DateInterval(ConvertStringToSecond::convertPeriod($time)));

                if ($validityAccess && $blockTime->format('F j Y H:i:s') > $validityAccess) {
                    return false;
                }

                $nextLogin = $this->expirationTime->getNextLogin(
                    $radcheckTimeLeftAux,
                    $period,
                    $time
                );

                if ($this->expirationTime->isTimeExpired($radcheckTimeLeft, $nextLogin)) {
                    $nextLoginTimezoneBased = $this->expirationTime->UTCToTimezoneBased(clone $nextLogin, $apTimezone);

                    $analyticEvent = new Event();
                    $event = $analyticEvent->withClient($client)
                        ->withEventIdentifier(EventIdentifier::BLOCK_BY_TIME)
                        ->withEventType(EventType::BLOCK_BY_TIME)
                        ->withRequest(null)
                        ->withGuest($guest)
                        ->withNas($nas)
                        ->withSession($this->session)
                        ->withExtraData(null)
                        ->build();

                    $this->logManager->sendLog($event);


                    return $this->render(
                        'FrontendBundle:SignIn:signInBlockPerTime.html.twig',
                        [
                            'period'    => $nextLoginTimezoneBased->format('d/m/Y à\\s H:i:s'),
                            'guest'     => $guest,
                            'template' => $this->templateService->templateSettings($this->session->get('campaignId')),
                            'customMessage' => false
                        ]
                    );
                } else {
                    $this->expirationTime->update($client, $guestMysql, $radcheckTimeLeftEntity, $blockTime, $apTimezone, $group->getId());
                }
            }

	        $this->session->set('timelimitModule', TimeLimitPolicy::BLOCK_PER_TIME);
        }

        return false;
    }
}
