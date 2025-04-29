<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Carbon\Carbon;
use SoftDeleteable\Fixture\Entity\Module;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\CampaignDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Gateways\Survey\GetSurveyGateway;
use Wideti\DomainBundle\Gateways\Survey\SurveyResponse;
use Wideti\DomainBundle\Helpers\CampaignDtoHelper;
use Wideti\DomainBundle\Helpers\DeviceHelper;
use Wideti\DomainBundle\Helpers\FacebookRedirectLikeAndShareHelper;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\CampaignCallToAction\CreateAccessDataService;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Campaign\CampaignAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\FrontendBundle\Controller\SurveyAuthController;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Deskbee\DeskbeeService;
use Wideti\DomainBundle\Helpers\NasHelper;

/**
 * Class NasCampaignStep
 * @package Wideti\DomainBundle\Service\NasManager
 */
class NasCampaignStep implements NasStepInterface
{
    use GuestServiceAware;
    use SessionAware;
    use EntityManagerAware;
    use CampaignAware;
    use FormAware;
    use LoggerAware;
    use TwigAware;

    /**
     * @var FrontendControllerHelper
     */
    public $controllerHelper;

    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
	 * @var CreateAccessDataService
	 */
	private $callToActionService;
    /**
     * @var GuestDevices
     */
    private $guestDevices;
    private $flowPlayerToken;

    /**
     * @var EventLoggerManager
     */
    private $logManager;

    /**
     * @var ModuleService
     */
    private $moduleService;

    /**
     * @var GetSurveyGateway
     */
    private $surveyGateway;

    /**
     * @var string
     */
    private $surveyFrontendAddress;

    /**
     * @var AccessPointsService
     */
    private $accessPointService;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var DeskbeeService
     */
    private $deskbeeService;


    /**
     * NasCampaignStep constructor.
     * @param CustomFieldsService $customFieldsService
     * @param ConfigurationService $configurationService
     * @param CacheServiceImp $cacheService
     * @param CreateAccessDataService $callToActionService
     * @param GuestDevices $guestDevices
     * @param $flowPlayerToken
     * @param EventLoggerManager $logManager
     * @param GetSurveyGateway $surveyGateway
     * @param ModuleService $moduleService
     * @param string $surveyFrontendAddress
     * @param DeskbeeService $deskbeeService
     */
    public function __construct(
        CustomFieldsService $customFieldsService,
        ConfigurationService $configurationService,
        CacheServiceImp $cacheService,
        CreateAccessDataService $callToActionService,
        GuestDevices $guestDevices,
        $flowPlayerToken,
        EventLoggerManager $logManager,
        GetSurveyGateway $surveyGateway,
        ModuleService $moduleService,
        $surveyFrontendAddress,
        AccessPointsService $accessPointService,
        RequestStack $requestStack,
        DeskbeeService $deskbeeService
    ) {
        $this->customFieldsService   = $customFieldsService;
        $this->configurationService  = $configurationService;
        $this->cacheService          = $cacheService;
	    $this->callToActionService   = $callToActionService;
        $this->guestDevices          = $guestDevices;
        $this->flowPlayerToken       = $flowPlayerToken;
        $this->logManager            = $logManager;
        $this->surveyGateway         = $surveyGateway;
        $this->moduleService         = $moduleService;
        $this->surveyFrontendAddress = $surveyFrontendAddress;
        $this->accessPointService    = $accessPointService;
        $this->requestStack          = $requestStack;
        $this->deskbeeService        = $deskbeeService;
    }

    /**
     * @param Guest $guest
     * @param Nas|null $nas
     * @param Client $client
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        $campaignId         = $this->session->get('campaignId');
        $this->callToActionService->updateCallToActionGuestId($guest, $nas, $campaignId);
        $posLoginCampaign   = $this->preparePosLoginCampaign($campaignId, $nas);
        $facebookHelper     = new FacebookRedirectLikeAndShareHelper($this->session);

        $isAppleUser    = DeviceHelper::checkAppleUser($this->session->get("userAgent"));
        $accessDataInfo = $this->guestDevices->accessDataInfo();

        $this->guestService->updateLastAccess($guest);
        $this->guestService->updateLastPolicyIdCreated($guest, $nas->getRadiusPolicy()->getId());


        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        $apVendor = $this->accessPointService->findByClientAndIdentifier($client->getId(), $nas->getAccessPointMacAddress());
        $isSurveyModuleActive = $this->moduleService->modulePermission('survey');
        $originDomain = $_SERVER["HTTP_HOST"];

        if ($apVendor && $isSurveyModuleActive) {
            //verificando pesquisa
            $result = $this->surveyGateway->get($client, $guest, $apVendor->getGroup()->getId(), $locale);

            if ($this->shouldShowSurvey($result)) {
                $redirectUrl = str_replace(
                    "<domain>", $originDomain, $this->surveyFrontendAddress);

                $userData = ["_dun" => $guest->get($guest->getLoginField())];
                $json = json_encode($userData);
                $base64Encoded = base64_encode($json);
                $redirectUrl = $redirectUrl . "/{$result->getId()}?client_id={$client->getId()}&guest_id={$guest->getMysql()}&t={$base64Encoded}";
                return new RedirectResponse($redirectUrl);
            }
        }

        try {
            $username = "#" . $client->getId() . "#" . $nas->getRadiusPolicy()->getId();
            $form = $this->form->createNamed(
                '',
                NasHelper::formVendorsMap[$nas->getVendorName()],
                null,
                [
                    'action'    => $nas->getNasFormPost()->getPostFormUrl(),
                    'username'  => $username,
                    'password'  => $nas->getRadiusPolicy()->getGuest()->getPassword()
                ]
            );

        } catch (\Exception $ex) {
            $this->logger->addCritical($ex->getMessage());
            throw new \Exception($ex->getMessage());
        }

	    $calllToAction = null;

        if ($posLoginCampaign) {
	        $calllToAction = $posLoginCampaign->getCallToAction();
        }

        $preloginMediaType = $posLoginCampaign !== null ? $posLoginCampaign->getPosLoginMediaType() : '';

        $posLoginView = "FrontendBundle:General:formPostImageController.html.twig";
        if ($preloginMediaType === CampaignDto::MEDIA_VIDEO) {
            $posLoginView = "FrontendBundle:General:formPostVideoController.html.twig";
        }

        /**
         * TODO -- (WSPOTNEW-3603)
         * TODO -- Aqui vamos ver se na sessao existe um atributo do visitante identificando que ele foi para tela
         * TODO -- de Curtir/Compartilhar. Caso tenha, removeremos da sessão
         */
        $hasFacebookShareOnSession = $facebookHelper->getFromSession($guest->getMysql());
        if ($hasFacebookShareOnSession) {
            $facebookHelper->removeFromSession($guest->getMysql());
        }

        if ($posLoginCampaign) {
            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::VIEW_POS_LOGIN_ACTION)
                ->withEventType(EventType::VIEW_POS_LOGIN_ACTION)
                ->withRequest(null)
                ->withGuest($guest)
                ->withNas($nas)
                ->withSession($this->session)
                ->withExtraData(
                    [
                        "campaign" => [
                            "campaign_id" => $posLoginCampaign ? $posLoginCampaign->getId() : null,
                            "campaign_name" => $posLoginCampaign ? $posLoginCampaign->getName() : null,
                            "campaign_pre_login" => $posLoginCampaign ? $posLoginCampaign->getPreLogin() : null,
                            "campaign_pre_media_type" => $posLoginCampaign ?
                                $posLoginCampaign->getPreLoginMediaType() :
                                null,
                            "campaign_pos_login" => $posLoginCampaign ? $posLoginCampaign->getPosLogin() : null,
                            "campaign_pos_media_type" => $posLoginCampaign ?
                                $posLoginCampaign->getPosLoginMediaTime() :
                                null
                        ]
                    ]
                )
                ->build();

            $this->logManager->sendLog($event);
        }

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($client)
            ->withEventIdentifier(EventIdentifier::LOGIN_SUBMITTED_TO_RADIUS)
            ->withEventType(EventType::LOGIN_SUBMITTED_TO_RADIUS)
            ->withRequest(null)
            ->withGuest($guest)
            ->withNas($nas)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);

        $this->deskbeeService->checkinAction($guest, $apVendor);

        $totalDesktopImages = 0;
        $totalMobileImages = 0;

        if ($posLoginCampaign) {
            if ($posLoginCampaign->getPosLoginMediaDesktop()) $totalDesktopImages++;
            if ($posLoginCampaign->getPosLoginMediaDesktop2()) $totalDesktopImages++;
            if ($posLoginCampaign->getPosLoginMediaDesktop3()) $totalDesktopImages++;

            if ($posLoginCampaign->getPosLoginMediaMobile()) $totalMobileImages++;
            if ($posLoginCampaign->getPosLoginMediaMobile2()) $totalMobileImages++;
            if ($posLoginCampaign->getPosLoginMediaMobile3()) $totalMobileImages++;
        }

        return $this->render(
            $posLoginView,
            [
                'isAppleUser'       => $isAppleUser,
                'flowPlayerToken'   => $this->flowPlayerToken,
                'wspotNas'          => $nas,
                'device'            => $accessDataInfo['device'],
                'campaign'          => $posLoginCampaign,
                'callToAction'      => $posLoginCampaign ? $calllToAction : null,
                'landscapeClass'    => $calllToAction ? "{$calllToAction->getLandscapeButtonVerticalAlign()}_{$calllToAction->getLandscapeButtonHorizontalAlign()}" : "",
                'portraitClass'     => $calllToAction ? "{$calllToAction->getPortraitButtonVerticalAlign()}_{$calllToAction->getPortraitButtonHorizontalAlign()}" : "",
                'password'          => $guest->getPassword(),
                'guestId'           => $guest->getMysql() ?: 'null',
                'form'              => $form->createView(),
                'guestMacAddress'   => $nas->getGuestDeviceMacAddress() ?: 'null',
                'accessPoint'       => $nas->getAccessPointMacAddress() ?: 'null',
                'totalDesktopImages'=> $totalDesktopImages,
                'totalMobileImages' => $totalMobileImages,
            ]
        );
    }

    /**
     * @param $campaignId
     * @param Nas|null $nas
     * @return object|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function preparePosLoginCampaign($campaignId, Nas $nas = null)
    {
        $client         = $this->session->get('wspotClient');
        $redirectUrl    = $this->configurationService->get($nas, $client, 'redirect_url');

        if (!$this->session->get("callToActionUrlIsSet")) {
            $this->session->set("redirectUrl", $redirectUrl);
        }


        $campaign    = $this->campaignService->getById($campaignId);
        $campaignDto = [];

        if ($campaign !== null) {
            $campaignDto = CampaignDtoHelper::convert($campaign);
        }

        $accessPoint    = $this->em
            ->getRepository("DomainBundle:AccessPoints")
            ->getAccessPoint($nas->getAccessPointMacAddress(), $this->getLoggedClient(), null);


        if ($campaignDto) {
            $videoSkip = $this->em
                ->getRepository("DomainBundle:VideoSkip")
                ->findOneBy([
                    'campaignId'  => $campaignDto->getId(),
                    'step'      => 'pos'
                ]);
            $campaignDto->setVideoSkip($videoSkip);

            $campaignHours = $campaignDto->getHours()->toArray();
            foreach ($campaignHours as $campaignHour) {
                $startTime  = $campaignHour->getStartTime();
                $endTime    = $campaignHour->getEndTime();
                $apTimezone = $accessPoint ? $accessPoint->getTimezone() : TimezoneService::DEFAULT_TIMEZONE;

                $now        = Carbon::now(new \DateTimeZone($apTimezone));
                $hourFrom   = Carbon::createFromTimeString($startTime)->setTimezone($apTimezone);
                $hourTo     = Carbon::createFromTimeString($endTime)->setTimezone($apTimezone);

                if ($now->gte($hourFrom) && $now->lte($hourTo)) {
                    if (!$this->session->get("callToActionUrlIsSet")) {
                        $redirectUrl = $campaignDto->getRedirectUrl() ?: $redirectUrl;
                        $this->session->set('redirectUrl', $redirectUrl);
                    }

                    return $campaignDto;
                }
            }
        }

        return null;
    }

    /**
     * @param SurveyResponse $result
     * @param $surveyWasAnswered
     * @param $isSurveyModuleActive
     * @return bool
     */
    private function shouldShowSurvey(SurveyResponse $result)
    {
        $surveyWasAnswered = $this->session->get(SurveyAuthController::SURVEY_ANSWERED_SESSION_KEY);
        return $result->isShowSurvey() && !$surveyWasAnswered;
    }
}
