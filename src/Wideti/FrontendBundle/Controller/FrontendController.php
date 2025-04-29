<?php

namespace Wideti\FrontendBundle\Controller;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\FrontendBundle\Form\SignInType;
use Wideti\FrontendBundle\Form\SignUpType;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\CampaignDto;
use Wideti\DomainBundle\Dto\CampaignViewsDto;
use Wideti\DomainBundle\Dto\OneGuestQueryDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessPointExtraConfig;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\CampaignViews;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Event\AuthenticationEvent;
use Wideti\DomainBundle\Exception\AccessPointNotRegisteredException;
use Wideti\DomainBundle\Exception\ClientWasDisabledException;
use Wideti\DomainBundle\Exception\ConsentErrorException;
use Wideti\DomainBundle\Exception\EmptyUserAgentException;
use Wideti\DomainBundle\Exception\NasEmptyException;
use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Gateways\Consents\ListSignatureGateway;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\AuditLogsHelper;
use Wideti\DomainBundle\Helpers\CampaignDtoHelper;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\Cookie\CookieHelper;
use Wideti\DomainBundle\Helpers\DeviceHelper;
use Wideti\DomainBundle\Helpers\LanguageHelper;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeServiceImp;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsExtraConfigService;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsServiceAware;
use Wideti\DomainBundle\Service\Auth\AuthServiceAware;
use Wideti\DomainBundle\Service\Blacklist\BlacklistServiceAware;
use Wideti\DomainBundle\Service\BusinessHours\BusinessHoursServiceAware;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Campaign\CampaignAware;
use Wideti\DomainBundle\Service\Campaign\CampaignSelectorService;
use Wideti\DomainBundle\Service\Campaign\Selectors\CampaignSelector;
use Wideti\DomainBundle\Service\CampaignCallToAction\CreateAccessDataService;
use Wideti\DomainBundle\Service\ClientSelector\ClientSelectorServiceAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\ExpirationTime\ExpirationTimeImp;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Hubsoft\HubsoftService;
use Wideti\DomainBundle\Service\Ixc\IxcService;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\MacAddressAuthentication\MacAddressAuthenticationImp;
use Wideti\DomainBundle\Service\NasCustomMiddleware\NasMiddlewareManager;
use Wideti\DomainBundle\Service\NasManager\NasService;
use Wideti\DomainBundle\Service\Notification\Dto\Message;
use Wideti\DomainBundle\Service\Notification\NotificationService;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Service\TwoFactorAuth\TwoFactorAuthService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasFactory;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;

/**
 * Class FrontendController
 * @package Wideti\FrontendBundle\Controller
 */
class FrontendController implements NasControllerHandler
{
    use SessionAware;
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use TemplateAware;
    use CampaignAware;
    use AuthServiceAware;
    use BlacklistServiceAware;
    use BusinessHoursServiceAware;
    use GuestServiceAware;
    use TranslatorAware;
    use EventDispatcherAware;
    use RouterServiceAware;
    use ClientSelectorServiceAware;
    use AccessPointsServiceAware;
    use LoggerAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    private $bounceValidatorActive;
    private $twoFactorAuthHapVida;
    private $twoFactorAuthAccessCode;

    /** @var NasMiddlewareManager */
    private $nasMiddlewareManager;
    /**
     * @var NotificationService
     */
    private $nasBadParameterNotification;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var NotificationService
     */
    private $apNotRegisteredNotification;
    /**
     * @var CampaignSelectorService
     */
    private $campaignSelectorService;
    /**
     * @var NasService
     */
    private $nasService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var CreateAccessDataService
     */
    private $createAccessDataService;
    /**
     * @var AccessCodeServiceImp
     */
    private $accessCodeService;
    /**
     * @var MacAddressAuthenticationImp
     */
    private $macAddressAuthentication;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    private $flowPlayerToken;

    /**
     * @var EventLoggerManager
     */
    private $logManager;

    private $autoLoginSecretKey;

    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;
    /**
     * @var ListSignatureGateway
     */
    private $listSignatureGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var AccessPointsExtraConfigService $extraConfigService
     */
    private $extraConfigService;

    /**
     * @var ExpirationTimeImp
     */
    private $expirationTime;

    /**
     * @var HubsoftService
     */
    private $hubsoftService;

    /**
     * @var IxcService
     */
    private $IxcService;

    /**
     * FrontendController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param TwoFactorAuthService $twoFactorAuthHapVida
     * @param TwoFactorAuthService $twoFactorAuthAccessCode
     * @param NasMiddlewareManager $nasMiddlewareManager
     * @param NotificationService $nasBadParameterNotification
     * @param NotificationService $apNotRegisteredNotification
     * @param CampaignSelectorService $campaignSelectorService
     * @param NasService $nasService
     * @param CacheServiceImp $cacheService
     * @param CreateAccessDataService $createAccessDataService
     * @param AccessCodeServiceImp $accessCodeService
     * @param MacAddressAuthenticationImp $macAddressAuthentication
     * @param GuestDevices $guestDevices
     * @param $bounceValidatorActive
     * @param $flowPlayerToken
     * @param EventLoggerManager $logManager
     * @param string $autoLoginSecretKey
     * @param GetConsentGateway $getConsentGateway
     * @param ListSignatureGateway $listSignatureGateway
     * @param AccessPointsExtraConfigService $extraConfigService
     * @param ExpirationTimeImp $expirationTime
     * @param HubsoftService $hubsoftService
     * @param Ixcervice $Ixcervice

     */
    public function __construct(
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        TwoFactorAuthService $twoFactorAuthHapVida,
        TwoFactorAuthService $twoFactorAuthAccessCode,
        NasMiddlewareManager $nasMiddlewareManager,
        NotificationService $nasBadParameterNotification,
        NotificationService $apNotRegisteredNotification,
        CampaignSelectorService $campaignSelectorService,
        NasService $nasService,
        CacheServiceImp $cacheService,
        CreateAccessDataService $createAccessDataService,
        AccessCodeServiceImp $accessCodeService,
        MacAddressAuthenticationImp $macAddressAuthentication,
        GuestDevices $guestDevices,
        $bounceValidatorActive,
        $flowPlayerToken,
        EventLoggerManager $logManager,
        $autoLoginSecretKey,
        GetConsentGateway $getConsentGateway,
        ListSignatureGateway $listSignatureGateway,
        LegalBaseManagerService $legalBaseManagerService,
        AccessPointsExtraConfigService $extraConfigService,
        ExpirationTimeImp $expirationTime,
        HubsoftService $hubsoftService,
        IxcService $IxcService

    ) {
        $this->configurationService         = $configurationService;
        $this->controllerHelper             = $controllerHelper;
        $this->twoFactorAuthHapVida         = $twoFactorAuthHapVida;
        $this->twoFactorAuthAccessCode      = $twoFactorAuthAccessCode;
        $this->nasMiddlewareManager         = $nasMiddlewareManager;
        $this->nasBadParameterNotification  = $nasBadParameterNotification;
        $this->apNotRegisteredNotification  = $apNotRegisteredNotification;
        $this->campaignSelectorService      = $campaignSelectorService;
        $this->nasService                   = $nasService;
        $this->cacheService                 = $cacheService;
        $this->createAccessDataService      = $createAccessDataService;
        $this->accessCodeService            = $accessCodeService;
        $this->macAddressAuthentication     = $macAddressAuthentication;
        $this->guestDevices                 = $guestDevices;
        $this->bounceValidatorActive        = $bounceValidatorActive;
        $this->flowPlayerToken              = $flowPlayerToken;
        $this->logManager                   = $logManager;
        $this->autoLoginSecretKey           = $autoLoginSecretKey;
        $this->getConsentGateway            = $getConsentGateway;
        $this->listSignatureGateway         = $listSignatureGateway;
        $this->legalBaseManager             = $legalBaseManagerService;
        $this->extraConfigService           = $extraConfigService;
        $this->hotel_viva_domains           = ["hotelviva.cp.vagalumewifi.com.br", "testhotelviva", "hotelsolar.cp.vagalumewifi.com.br"];
        $this->expirationTime               = $expirationTime;
        $this->hubsoftService               = $hubsoftService;
        $this->IxcService                   = $IxcService;

    }

    /**
     * @param Request $request
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws AccessPointNotRegisteredException
     * @throws NasEmptyException
     * @throws NasWrongParametersException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \ReflectionException
     */
    public function handleParameters(Request $request)
    {
        $nasParams = [];
        $client = $this->getLoggedClient();

        if ($request->isMethod('GET')) {
            $nasParams = $request->query->all();
        } elseif ($request->isMethod('POST')) {
            $nasParams = $request->request->all();
        }

        $vendorName     = $request->get('nas');
        $redirectResult = $this->ignoreVendorCallbackParameters($nasParams, $vendorName);

        if ($redirectResult) {
            return $redirectResult;
        }
        
        $apConfig = null;
        if ($vendorName == 'ruckus_cloud') {
            $ruckusMac = NasHelper::makeMacByVendor($vendorName, $nasParams);
            $ap = $this->accessPointsService->findByClientAndIdentifier($client, $ruckusMac);
            if (is_null($ap)) {
                throw new NasWrongParametersException("Access Point not found");
            }
            $apConfig = $this->extraConfigService->findExtraConfigByAp($ap);

        } elseif (in_array($vendorName, ['tp_link_cloud', 'tp_link_v4_cloud', 'tp_link_v5_cloud', 'unifi', 'unifinew'])) {
            $tpLinkMac = NasHelper::makeMacByVendor($vendorName, $nasParams);
            $ap = $this->accessPointsService->findByClientAndIdentifier($client, $tpLinkMac);
            if (is_null($ap)) {
                throw new NasWrongParametersException("Access Point not found");
            }

            $apConfig = $this->extraConfigService->findExtraConfigByAp($ap);

            if ($vendorName === "unifinew" || $vendorName === "unifi") {
                $controllerUrl = $this->em
                    ->getRepository("DomainBundle:AccessPoints")
                    ->getAPController($tpLinkMac, $client->getId());
                
                if ($controllerUrl) {
                    $apConfig = new AccessPointExtraConfig();
                    $apConfig->setConfigType("controllerUrl");
                    $apConfig->setValue($controllerUrl);
                }
                $nasParams["client_domain"] = $client->getDomain();
            }
        }
        $nas = NasFactory::factory($vendorName, $nasParams, $apConfig);        
        

        if (in_array($nas->getVendorName(), ['cisco_meraki_cloud', 'watchguard'])) {
            $this->session->set('redirect_url', $nas->getVendorRawParameters()['continue_url']);
        } elseif ($nas->getVendorName() == 'xirrus') {
            $this->session->set('xirrusChallenge', $nas->getVendorRawParameters()['challenge']);
            $this->session->set('uamip', $nas->getVendorRawParameters()['uamip']);
            $this->session->set('uamport', $nas->getVendorRawParameters()['uamport']);
            $this->session->set('xirrus_redirect_url', $nas->getVendorRawParameters()['userurl']);
        }

        $this->session->set(Nas::NAS_SESSION_KEY, $nas);
        $this->clientSelectorService->define($request->getHost());

        $this->accessPointsService->checkIfAreRegistered($client, $nas);

        if ($client->getApCheck()) {
            $this->accessPointsService->verifyAccessPoint($nas, $client);
        }


        $configMap = $this->configurationService->getConfigurationMap($nas, $client);
        $this->controllerHelper->setTwigGlobalVariable('config', $configMap);
    }

    /**
     * @param array $nasParams
     * @param string $vendorName
     * @return \Symfony\Component\HttpFoundation\RedirectResponse | null
     * @throws NasEmptyException
     */
    private function ignoreVendorCallbackParameters(array $nasParams, $vendorName)
    {
        if (empty($nasParams)) {
            throw new NasEmptyException('Parameters empty in factory handle');
        }

        if (count($nasParams) > 1) {
            return null;
        }

        /** @var Nas $sessionNas */
        $sessionNas = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($vendorName == Vendor::MIKROTIK && isset($nasParams['submit'])) {
            if ($sessionNas) {
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('frontend_index')
                );
            }
            throw new NasEmptyException('Parameters empty in factory handle');
        } elseif ($vendorName == Vendor::FORTINET && isset($nasParams['Auth'])) {
            if ($sessionNas) {
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('frontend_index')
                );
            }
            throw new NasEmptyException('Parameters empty in factory handle');
        }

        return null;
    }

    /**
     * @param Request $request
     * @return bool|string
     */
    public function getBrowserLanguage(Request $request)
    {
        $language = substr($request->server->get('HTTP_ACCEPT_LANGUAGE'), 0, 2);

        if (!in_array($language, ['en', 'es'])) {
            $language = 'pt_br';
        }
        return $language;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function preLoginAction(Request $request)
    {
        $preview = ($request->get('preview') == '1') ? true : false;
        $this->session->set('preview', $preview);

        $this->removeCache();
        $browserLanguage = 'pt_br';
        $client = $this->getLoggedClient();
        $this->session->set("userAgent", $request->headers->get('User-Agent'));
        $date = new \DateTime();
        $this->session->set('amplitude_session_id', $date->getTimestamp());
        $uri = $request->getUri();
        $clientDomain = $client->getDomain();
        $uri = str_replace('http://'.$clientDomain, 'https://'.$clientDomain, $uri);
        if(strpos($uri, 'unifinew')) {
            $uri = str_replace('unifinew', 'unifi', $uri);
        }

        $this->session->set('request_url_origin', $uri);

        try {
            $this->handleParameters($request);
        } catch (NasEmptyException $e) {
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    "frontend_preview"
                )
            );
        } catch (NasWrongParametersException $e) {
            $this->nasBadParameterNotification->notify($client, new Message(Message::ERROR, $e->getMessage()));

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::BAD_PARAMETER_AP)
                ->withEventType(EventType::BAD_PARAMETER_AP)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    "frontend_bad_parameter_ap_error",
                    ['message' => $e->getMessage()]
                )
            );
        } catch (AccessPointNotRegisteredException $e) {
            $this->apNotRegisteredNotification->notify($client, new Message(Message::WARNING, $e->getMessage()));

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::NONEXISTENT_AP)
                ->withEventType(EventType::NONEXISTENT_AP)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    "frontend_ap_not_registered",
                    ['message' => $e->getMessage()]
                )
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($this->configurationService->get($nas, $client, 'translation')) {
            $browserLanguage = $this->getBrowserLanguage($request);
        }
        $accessDataInfo = $this->guestDevices->accessDataInfo();

        if (array_key_exists('res', $nas->getVendorRawParameters()) && $nas->getVendorRawParameters()['res'] == 'success') {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_redirection_url'));
        }

        $accessPoint = $this->em
            ->getRepository("DomainBundle:AccessPoints")
            ->getAccessPoint($nas->getAccessPointMacAddress(), $this->getLoggedClient(), null);

        if ($accessPoint && $accessPoint->getStatus() == AccessPoints::INACTIVE) {
            $template = $this->templateService->templateSettings($this->session->get('campaignId'));

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::INACTIVE_AP)
                ->withEventType(EventType::INACTIVE_AP)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->render(
                'FrontendBundle:General:accessPointInactive.html.twig',
                [
                    'template'    => $template,
                    'templateCSS' => $template->getBackgroundCSSConfiguration(),
                    'accessPoint' => $accessPoint
                ]
            );
        }

        $redirectUrl = $this->controllerHelper->generateUrl(
		    'frontend_index',
		    [
			    '_locale' => $browserLanguage
		    ]
	    );

        $isAppleUser = DeviceHelper::checkAppleUser($this->session->get("userAgent"));
        $campaign    = $this->preparePreLoginCampaign($accessPoint, $nas, $client, CampaignSelector::PRE_LOGIN);
        $preloginMediaType = $campaign !== null ? $campaign->getPreLoginMediaType() : '';

        $preLoginView = "FrontendBundle:General:preLoginImage.html.twig";
        if ($preloginMediaType === CampaignDto::MEDIA_VIDEO) {
            $preLoginView = "FrontendBundle:General:preLoginVideo.html.twig";
        }
	    if ($campaign && $campaign->getPreLogin()) {
		    $calllToAction = $campaign->getCallToAction();

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::VIEW_PRE_LOGIN_ACTION)
                ->withEventType(EventType::VIEW_PRE_LOGIN_ACTION)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(
                    [
                        "campaign" => [
                            "campaign_id" => $campaign->getId(),
                            "campaign_name" => $campaign->getName(),
                            "campaign_pre_login" => $campaign->getPreLogin(),
                            "campaign_pre_media_type" => $campaign->getPreLoginMediaType(),
                            "campaign_pos_login" => $campaign->getPosLogin(),
                            "campaign_pos_media_type" => $campaign->getPosLoginMediaTime()
                        ]
                    ]
                )
                ->build();

            $this->logManager->sendLog($event);

            $totalDesktopImages = 0;

            if ($campaign->getPreLoginMediaDesktop()) $totalDesktopImages++;
            if ($campaign->getPreLoginMediaDesktop2()) $totalDesktopImages++;
            if ($campaign->getPreLoginMediaDesktop3()) $totalDesktopImages++;

            $totalMobileImages = 0;

            if ($campaign->getPreLoginMediaMobile()) $totalMobileImages++;
            if ($campaign->getPreLoginMediaMobile2()) $totalMobileImages++;
            if ($campaign->getPreLoginMediaMobile3()) $totalMobileImages++;


            return $this->render(
                $preLoginView,
                [
                    'device'            => $accessDataInfo['device'],
                    'isAppleUser'       => $isAppleUser,
                    'flowPlayerToken'   => $this->flowPlayerToken,
                    'campaign'          => $campaign,
                    'callToAction'      => $calllToAction,
                    'landscapeClass'    => $calllToAction ? "{$calllToAction->getLandscapeButtonVerticalAlign()}_{$calllToAction->getLandscapeButtonHorizontalAlign()}" : "",
                    'portraitClass'     => $calllToAction ? "{$calllToAction->getPortraitButtonVerticalAlign()}_{$calllToAction->getPortraitButtonHorizontalAlign()}" : "",
                    'urlRedirect'       => $redirectUrl,
                    'guestMacAddress'   => $nas->getGuestDeviceMacAddress() ?: 'null',
                    'accessPoint'       => $accessPoint ? $accessPoint->getIdentifier() : 'null',
                    'totalDesktopImages'=> $totalDesktopImages,
                    'totalMobileImages' => $totalMobileImages,
                ]
            );
        }

        return $this->controllerHelper->redirect($redirectUrl);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function previewAction(Request $request)
    {
        $apIdentifier = !empty($request->get('ap')) ? $request->get('ap') : null;
        $campaignId   = !empty($this->session->get('campaignId')) ? $this->session->get('campaignId') : null;
        $client       = $this->session->get('wspotClient');
        $this->session->set("isWhiteLabel", $client->isWhiteLabel());
        $template = $this->templateService->getTemplateBy($client, $apIdentifier, $campaignId);

        $configMap = $this->configurationService->getByIdentifierOrDefault($apIdentifier, $client);
        $this->controllerHelper->setTwigGlobalVariable('config', $configMap);

        $guest = new Guest();
        $guest->setLocale($request->getLocale());

        $loginForm  = $this->controllerHelper->createForm(SignInType::class, $guest, [
            'method' => 'POST'
        ]);

        $validationGroups = [];
        if ($configMap["authorize_email"]) {
            array_push($validationGroups, 'authorize_email');
        }

        $signUpForm = $this->controllerHelper->createForm(SignUpType::class, $guest, [
            'method' => 'POST',
            'attr'   => $validationGroups
        ]);

        $autUrl = "#";
        $isMobile = $this->isMobileWebView();
        $compatibleOpenExternalBrowser = $this->compatibleOpenExternalBrowser($isMobile);
        $requestOptin = $this->configurationService->get(null, $client, 'request_optin');

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($client)
            ->withEventIdentifier(EventIdentifier::PREVIEW_ACCESS)
            ->withEventType(EventType::PREVIEW_ACCESS)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);
        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        // HUBSOFT
        $hubsoftIntegrationIsActive = $this->hubsoftService->isActive($client);
        $showHubsoftAuth = false;
        $hubsoftProspectIsActive = false;
        $hubsoftAuthButton = "";
        $hubsoftButtonColor = "";
        if ($hubsoftIntegrationIsActive) {
            $showHubsoftAuth = $this->hubsoftService->shouldAuthClient($client);
            $hubsoftProspectIsActive = $this->hubsoftService->shouldSendProspect($client);
        }
        if ($showHubsoftAuth) {
            $hubsoftButtonColor = $this->hubsoftService->getButtonColor($client);
            $hubsoftAuthButton = $this->hubsoftService->getAuthButtonText($client);
        }

        // IXC 
        $IxcIntegrationIsActive = $this->IxcService->isActive($client);
        $showIxcAuth = false;
        $IxcProspectIsActive = false;
        $IxcAuthButton = "";
        $IxcButtonColor = "";
        if ($IxcIntegrationIsActive) {
            $showIxcAuth = $this->IxcService->shouldAuthClient($client);
            $IxcProspectIsActive = $this->IxcService->shouldSendProspect($client);
        }
        if ($showIxcAuth) {
            $IxcButtonColor = $this->IxcService->getButtonColor($client);
            $IxcAuthButton = $this->IxcService->getAuthButtonText($client);
        }

        $clientDomain = $this->getLoggedClient()->getDomain();
        if (in_array($clientDomain, $this->hotel_viva_domains)) {
            return $this->render(
                'FrontendBundle:SignIn:loginHotelViva.html.twig',
                [
                    'template'                      => $template,
                    'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                    'login_form'                    => $loginForm->createView(),
                    'login_error'                   => null,
                    'signup_form'                   => $signUpForm->createView(),
                    'signup_error'                  => null,
                    'social_error'                  => null,
                    'emailUpdate'                   => null,
                    'bounceValidator'               => false,
                    'isMockView'                    => true,
                    'google_auth_url'               => $autUrl,
                    'isMobile'                      => $isMobile,
                    'compatibleOpenExternalBrowser' => $compatibleOpenExternalBrowser,
                    'requiredOptIn'                 => $requestOptin,
                    'oauths'                        => $this->getOauthLoginSources(),
                    'activeLegalBase'               => $activeLegalBase,
                    'client'                        => $client,
                ]
            );
        }

        return $this->render(
            'FrontendBundle:SignIn:login.html.twig',
            [
                'template'                      => $template,
                'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                'login_form'                    => $loginForm->createView(),
                'login_error'                   => null,
                'signup_form'                   => $signUpForm->createView(),
                'signup_error'                  => null,
                'social_error'                  => null,
                'emailUpdate'                   => null,
                'bounceValidator'               => false,
                'isMockView'                    => true,
                'google_auth_url'               => $autUrl,
                'isMobile'                      => $isMobile,
                'compatibleOpenExternalBrowser' => $compatibleOpenExternalBrowser,
                'requiredOptIn'                 => $requestOptin,
                'oauths'                        => $this->getOauthLoginSources(),
                'showHubsoftAuth'               => $showHubsoftAuth,
                'hubsoftProspectIsActive'       => $hubsoftProspectIsActive,
                'hubsoftAuthButton'             => $hubsoftAuthButton,
                'hubsoftButtonColor'            => $hubsoftButtonColor,
                'showIxcAuth'                   => $showIxcAuth,
                'IxcProspectIsActive'           => $IxcProspectIsActive,
                'IxcAuthButton'                 => $IxcAuthButton,
                'IxcButtonColor'                => $IxcButtonColor,
                'activeLegalBase'               => $activeLegalBase,
                'client'                        => $client
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function previewAdminAction(Request $request)
    {
        $template_id = $request->get('template_id');
        $template = $this->templateService->getTemplateById($template_id);
        $apIdentifier = !empty($request->get('ap')) ? $request->get('ap') : null;
        $campaignId   = !empty($this->session->get('campaignId')) ? $this->session->get('campaignId') : null;
        $client       = $this->session->get('wspotClient');
        $this->session->set("isWhiteLabel", $client->isWhiteLabel());

        $configMap = $this->configurationService->getByIdentifierOrDefault($apIdentifier, $client);
        $this->controllerHelper->setTwigGlobalVariable('config', $configMap);

        $guest = new Guest();
        $guest->setLocale($request->getLocale());

        $loginForm  = $this->controllerHelper->createForm(SignInType::class, $guest, [
            'method' => 'POST'
        ]);

        $validationGroups = [];
        if ($configMap["authorize_email"]) {
            array_push($validationGroups, 'authorize_email');
        }

        $signUpForm = $this->controllerHelper->createForm(SignUpType::class, $guest, [
            'method' => 'POST',
            'attr'   => $validationGroups
        ]);

        $autUrl = "#";
        $isMobile = $this->isMobileWebView();
        $compatibleOpenExternalBrowser = $this->compatibleOpenExternalBrowser($isMobile);
        $requestOptin = $this->configurationService->get(null, $client, 'request_optin');

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($client)
            ->withEventIdentifier(EventIdentifier::PREVIEW_ACCESS)
            ->withEventType(EventType::PREVIEW_ACCESS)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);
        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        // HUBSOFT
        $hubsoftIntegrationIsActive = $this->hubsoftService->isActive($client);
        $showHubsoftAuth = false;
        $hubsoftProspectIsActive = false;
        $hubsoftAuthButton = "";
        $hubsoftButtonColor = "";
        if ($hubsoftIntegrationIsActive) {
            $showHubsoftAuth = $this->hubsoftService->shouldAuthClient($client);
            $hubsoftProspectIsActive = $this->hubsoftService->shouldSendProspect($client);
        }
        if ($showHubsoftAuth) {
            $hubsoftAuthButton = $this->hubsoftService->getAuthButtonText($client);
            $hubsoftButtonColor = $this->hubsoftService->getButtonColor($client);
        }

        // IXC
        $IxcIntegrationIsActive = $this->IxcService->isActive($client);
        $showIxcAuth = false;
        $IxcProspectIsActive = false;
        $IxcAuthButton = "";
        $IxcButtonColor = "";
        if ($IxcIntegrationIsActive) {
            $showIxcAuth = $this->IxcService->shouldAuthClient($client);
            $IxcProspectIsActive = $this->IxcService->shouldSendProspect($client);
        }
        if ($showIxcAuth) {
            $IxcAuthButton = $this->IxcService->getAuthButtonText($client);
            $IxcButtonColor = $this->IxcService->getButtonColor($client);
        }

        $clientDomain = $this->getLoggedClient()->getDomain();
        if (in_array($clientDomain, $this->hotel_viva_domains)) {
            return $this->render(
                'FrontendBundle:SignIn:loginHotelViva.html.twig',
                [
                    'template'                      => $template,
                    'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                    'login_form'                    => $loginForm->createView(),
                    'login_error'                   => null,
                    'signup_form'                   => $signUpForm->createView(),
                    'signup_error'                  => null,
                    'social_error'                  => null,
                    'emailUpdate'                   => null,
                    'bounceValidator'               => false,
                    'isMockView'                    => true,
                    'google_auth_url'               => $autUrl,
                    'isMobile'                      => $isMobile,
                    'compatibleOpenExternalBrowser' => $compatibleOpenExternalBrowser,
                    'requiredOptIn'                 => $requestOptin,
                    'oauths'                        => $this->getOauthLoginSources(),
                    'activeLegalBase'               => $activeLegalBase,
                    'client'                        => $client,
                ]
            );
        }

        return $this->render(
            'FrontendBundle:SignIn:login.html.twig',
            [
                'template'                      => $template,
                'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                'login_form'                    => $loginForm->createView(),
                'login_error'                   => null,
                'signup_form'                   => $signUpForm->createView(),
                'signup_error'                  => null,
                'social_error'                  => null,
                'emailUpdate'                   => null,
                'bounceValidator'               => false,
                'isMockView'                    => true,
                'google_auth_url'               => $autUrl,
                'isMobile'                      => $isMobile,
                'compatibleOpenExternalBrowser' => $compatibleOpenExternalBrowser,
                'requiredOptIn'                 => $requestOptin,
                'oauths'                        => $this->getOauthLoginSources(),
                'showHubsoftAuth'               => $showHubsoftAuth,
                'hubsoftProspectIsActive'       => $hubsoftProspectIsActive,
                'hubsoftAuthButton'             => $hubsoftAuthButton,
                'hubsoftButtonColor'            => $hubsoftButtonColor,
                'showIxcAuth'                   => $showIxcAuth,
                'IxcProspectIsActive'           => $IxcProspectIsActive,
                'IxcAuthButton'                 => $IxcAuthButton,
                'IxcButtonColor'                => $IxcButtonColor,
                'activeLegalBase'               => $activeLegalBase,
                'client'                        => $client
            ]
        );
    }

    /**
	 * @param Request $request
	 * @return Response
	 */
    public function getAddressAction(Request $request) {
        if ($request->getMethod() !== "GET") {
		    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl("frontend_index"));
	    }

	    $zipCode = $request->get('cep');
        if (!$zipCode) {
            return new Response(json_encode(['error' => 'Zip code is required']), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        try {
            $guzzleClient = new \GuzzleHttp\Client();

            $apiResponse = $guzzleClient->request('GET', 'https://brasilapi.com.br/api/cep/v2/' . $zipCode);
            $body = $apiResponse->getBody()->getContents();

            return new Response($body, Response::HTTP_OK, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return new Response(json_encode(['error' => 'Could not fetch address', 'message' => $e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * @param Nas|null $nas
     * @param $autoLogin
     * @param $template
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function autoLogin(Nas $nas = null, $autoLogin, $template)
    {
        $client = $this->getLoggedClient();

        $isUniqueDeviceEnabled = false;

        if ($this->configurationService->isUniqueMacEnabled($client, $nas->getAccessPointMacAddress())) {
            $isUniqueDeviceEnabled = $this->configurationService
                ->isMacAlreadyRegistered($nas->getGuestDeviceMacAddress());
        }

        if ($autoLogin == '0' || !$this->authService->isActiveAutoLogin($nas)) {
            return false;
        }

        if (!isset($nas)) {
            return false;
        }

        $hasAutoLoginAvailableToThisDevice = $this->authService->validateAutoLogin([
            "client"            => $this->getLoggedClient(),
            "guestMacAddress"   => $nas->getGuestDeviceMacAddress(),
            "days"              => $this->configurationService->get($nas, $client, 'auto_login_days')
        ]);

        if ($hasAutoLoginAvailableToThisDevice === false) {
            return false;
        }

        /**
         * @var Guest $guest
         */
        $guest = $hasAutoLoginAvailableToThisDevice['guest'];

        if ($guest->getStatus() === Guest::STATUS_PENDING_APPROVAL || $guest->getStatus() === Guest::STATUS_BLOCKED) {
	        $manualConfirmation = $this->guestService->confirmationIfPendingApproval($guest, $client, $nas);

	        if (!$manualConfirmation) {
		        return $this->controllerHelper->redirect(
			        $this->controllerHelper->generateUrl('frontend_guest_confirmation', [
				        'guest' => $guest->getId()
			        ])
		        );
	        }
        }

        /**
         * @var DeviceEntry $deviceLastAccess
         */
        $deviceLastAccess = $hasAutoLoginAvailableToThisDevice['deviceLastAccess'];

        $apMacAddress = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'identifier'  => $deviceLastAccess->getLastApIdentifier(),
                'client'      => $this->getLoggedClient()
            ])
        ;

        $apTimezone = empty($deviceLastAccess) || empty($deviceLastAccess->getTimezone())
            ? TimezoneService::DEFAULT_TIMEZONE
            : $deviceLastAccess->getTimezone();

        $apIdentifier = empty($apMacAddress) || empty($apMacAddress->getIdentifier())
            ? $nas->getAccessPointMacAddress()
            : $apMacAddress->getIdentifier();

        $apFriendlyName = empty($apMacAddress) || empty($apMacAddress->getFriendlyName())
            ? ''
            : $apMacAddress->getFriendlyName();

        $lastAccessDateTime = $deviceLastAccess->getLastAccess()->setTimezone(new \DateTimeZone($apTimezone));

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($client)
            ->withEventIdentifier(EventIdentifier::AUTO_LOGIN_ACCESS)
            ->withEventType(EventType::AUTO_LOGIN_ACCESS)
            ->withNas($nas)
            ->withSession($this->session)
            ->withExtraData(null)
            ->withGuest($guest)
            ->build();

        $this->logManager->sendLog($event);

        $token = JWT::encode([
            "loginField" => $guest->getLoginField(),
            "loginValue" => $guest->getProperties()[$guest->getLoginField()],
            "iat" => time()
        ], $this->autoLoginSecretKey);
        $this->session->set('authorizeErrorUrlToken', $token);

        return $this->render(
            'FrontendBundle:SignIn:autoLogin.html.twig',
            [
                'guest'                     => $guest,
                'calledStationName'         => $apFriendlyName,
                'lastAccessPointVisited'    => $apIdentifier,
                'currentAccessPoint'        => $nas->getAccessPointMacAddress(),
                'date'                      => $lastAccessDateTime->format("d/m/Y H:i:s"),
                'template'                  => $template,
                'templateCSS'               => $template->getBackgroundCSSConfiguration(),
                'isUniqueDeviceEnabled'     => $isUniqueDeviceEnabled,
                'token'                     => $token
            ]
        );
    }

    /**
     * @param Request $request
     * @param null $signUpForm
     * @param null $loginForm
     * @param null $error
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Wideti\DomainBundle\Exception\CookieDisabledException
     */
    public function indexAction(Request $request, $signUpForm = null, $loginForm = null, $error = null)
    {
        if (strpos($request->getUri(), 'google') !== false) {
            return $this->controllerHelper->redirect($request->get('state').'?code='.$request->get('code'));
        }

        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        /**
         * @var Client $client
         */
        $client = $this->session->get(Client::SESSION_KEY);
        
        if (empty($client) ||$client->getStatus() == Client::STATUS_INACTIVE) {
            throw new ClientWasDisabledException('This client was disabled!');
        }

        if (!($nas instanceof Nas)) {
            $nas = null;
        }

        if ($nas == null || $client == null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        CookieHelper::checkCookieEnable();

        $template = $this->templateService->templateSettings($this->session->get('campaignId'));

        $traceHeaders = TracerHeaders::from($request);

        $legalBaseActive = $this->legalBaseManager->getActiveLegalBase($client);
        if (is_null($legalBaseActive)){
            return $this->render('@Frontend/FirstConfig/block.twig');
        } elseif ($legalBaseActive->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO){
            $consent = $this->getConsentGateway->get($client,'pt_BR', $traceHeaders);
            if ($consent->getHasError() && $consent->getError()->getCode() == 404) {
                return $this->render('@Frontend/FirstConfig/block.twig');
            }
        }

        if ($this->blacklistService->isBlocked($nas, $client->getId())) {

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::BLACKLISTED_DEVICE)
                ->withEventType(EventType::BLACKLISTED_DEVICE)
                ->withNas($nas)
                ->withSession($this->session)
                ->withExtraData(null)
                ->withGuest(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->render(
                '@Frontend/General/blacklistBlock.html.twig',
                [
                    'template' => $template,
                    'templateCSS' => $template->getBackgroundCSSConfiguration()
                ]
            );
        }

        $businessHours = $this->businessHoursService->checkAvailable($nas);

        if (!$businessHours->isAvailable()) {

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::OUT_OF_BUSINESS_HOUR)
                ->withEventType(EventType::OUT_OF_BUSINESS_HOUR)
                ->withNas($nas)
                ->withSession($this->session)
                ->withExtraData(null)
                ->withGuest(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->render(
                '@Frontend/General/businessHoursUnavailable.html.twig',
                [
                    'template' => $template,
                    'templateCSS' => $template->getBackgroundCSSConfiguration(),
                    'businessHours' => $businessHours
                ]
            );
        }

        if ($this->twoFactorAuthHapVida->isModuleActive() && !$this->session->get('twoFactorVerified')) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                'frontend_two_factor_auth_hapvida',
                [
                    '_locale' => $request->get('_locale')
                ]
            ));
        }


        $availableAccessCode = $this->accessCodeService->getAvailableAccessCodes($nas, AccessCode::STEP_LOGIN);

        if ($this->twoFactorAuthAccessCode->isModuleActive()
            && $availableAccessCode->isHasAccessCode() && !$this->session->get('twoFactorLoginVerified')) {
            $this->session->set('step', AccessCode::STEP_LOGIN);
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                'frontend_two_factor_auth_accesscode',
                [
                    '_locale' => $request->get('_locale'),
                    'step'    => AccessCode::STEP_LOGIN
                ]
            ));
        } else {
            $this->session->set('twoFactorLoginVerified', true);
            $this->session->set('twoFactorSignupVerified', false);
        }

        $this->session->set('locale', $request->get('_locale'));

        $accessPoint = $this->em
            ->getRepository("DomainBundle:AccessPoints")
            ->getAccessPoint($nas->getAccessPointMacAddress(), $this->getLoggedClient(), null);

        if ($accessPoint && $accessPoint->getStatus() == AccessPoints::INACTIVE) {
            $template = $this->templateService->templateSettings($this->session->get('campaignId'));

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::INACTIVE_AP)
                ->withEventType(EventType::INACTIVE_AP)
                ->withNas($nas)
                ->withSession($this->session)
                ->withExtraData(null)
                ->withGuest(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->render(
                'FrontendBundle:General:accessPointInactive.html.twig',
                [
                    'template'    => $template,
                    'templateCSS' => $template->getBackgroundCSSConfiguration(),
                    'accessPoint' => $accessPoint
                ]
            );
        }

        if ($client->getEnableMacAuthentication()) {
            $doMacAuthentication = $this->macAddressAuthentication->process($client, $nas);
            if ($doMacAuthentication) return $doMacAuthentication;
        }

        $autoLogin = $this->autoLogin($nas, $request->get('autoLogin'), $template);

        if ($autoLogin instanceof Response) {
            return $autoLogin;
        }

        if ($loginForm == null) {
            $loginForm = $this->controllerHelper->signInForm($request)->createView();
        }
        $apGroupId = $this->configurationService-> getApGroupId($nas, $client);

        if ($signUpForm == null) {
            $apGroupId = $this->configurationService-> getApGroupId($nas, $client);
            $signUpForm = $this->controllerHelper
                ->signUpForm($request, $this->configurationService->get($nas, $client, 'authorize_email'), $apGroupId)
                ->createView();
        }

        if (!empty($this->configurationService->get($nas, $client, 'enable_confirmation'))) {
            $guestApprovalPendent = $this->guestService
                ->findPendingApprovalByMacAddress($nas->getGuestDeviceMacAddress());

            if ($guestApprovalPendent) {
                $needConfirmationForm = $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('frontend_guest_confirmation', [
                        'guest' => $guestApprovalPendent->getId()
                    ])
                );

                if ($needConfirmationForm) {
                    return $needConfirmationForm;
                }
            }
        }

        $emailUpdate = null;

        if ($request->get('emailUpdate') == 1) {
            $emailUpdate = $this->translator->trans('wspot.invalid_email.email_updated');
        }

        $clientDomain = $request->getHttpHost();

        if ($clientDomain) {
	        $googleClient = $this->configurationService->getGoogleClient($clientDomain);
            $autUrl = $googleClient->createAuthUrl();
        } else {
            $autUrl = "#";
        }

        $isMobile = $this->isMobileWebView();
        $compatibleOpenExternalBrowser = $this->compatibleOpenExternalBrowser($isMobile);
        $requiredOptIn = (bool) $this->configurationService->get($nas, $client, 'request_optin');

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($client)
            ->withEventIdentifier(EventIdentifier::ACCESS_LOGIN_PAGE)
            ->withEventType(EventType::FORM_SIGNIN_ACCESS)
            ->withNas($nas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);
        $this->session->set("clientHost", $request->getHost());
        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        $clientDomain = $this->getLoggedClient()->getDomain();

        // HUBSOFT
        $hubsoftIntegrationIsActive = $this->hubsoftService->isActive($client);
        $showHubsoftAuth = false;
        $hubsoftProspectIsActive = false;
        $hubsoftAuthButton = "";
        $hubsoftButtonColor = "";
        if ($hubsoftIntegrationIsActive) {
            $showHubsoftAuth = $this->hubsoftService->shouldAuthClient($client);
            $hubsoftProspectIsActive = $this->hubsoftService->shouldSendProspect($client);
        }
        if ($showHubsoftAuth) {
            $hubsoftAuthButton = $this->hubsoftService->getAuthButtonText($client);
            $hubsoftButtonColor = $this->hubsoftService->getButtonColor($client);
        }

        // IXC 
        $IxcIntegrationIsActive = $this->IxcService->isActive($client);
        $showIxcAuth = false;
        $IxcProspectIsActive = false;
        $IxcAuthButton = "";
        $IxcButtonColor = "";
        if ($IxcIntegrationIsActive) {
            $showIxcAuth = $this->IxcService->shouldAuthClient($client);
            $IxcProspectIsActive = $this->IxcService->shouldSendProspect($client);
        }
        if ($showIxcAuth) {
            $IxcAuthButton = $this->IxcService->getAuthButtonText($client);
            $IxcButtonColor = $this->IxcService->getButtonColor($client);
        }


        if (in_array($clientDomain, $this->hotel_viva_domains)) {
            return $this->render(
                'FrontendBundle:SignIn:loginHotelViva.html.twig', 
                [
                    'vendorName'                    => $nas->getVendorName(),
                    'template'                      => $template,
                    'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                    'login_form'                    => $loginForm,
                    'login_error'                   => $error,
                    'signup_form'                   => $signUpForm,
                    'signup_error'                  => null,
                    'social_error'                  => $request->get('socialError'),
                    'emailUpdate'                   => $emailUpdate,
                    'bounceValidator'               => (int) $this->bounceValidatorActive,
                    'google_auth_url'               => $autUrl,
                    'isMobile'                      => $isMobile,
                    'compatibleOpenExternalBrowser' => $compatibleOpenExternalBrowser,
                    'requiredOptIn'                 => $requiredOptIn,
                    'oauths'                        => $this->getOauthLoginSources(),
                    'activeLegalBase'               => $activeLegalBase,
                    'client'                        => $client,
                    'isGoogleAuth'                  => $this->isGoogleAuth(),
                ]
            );
        }
        
        return $this->render(
            'FrontendBundle:SignIn:login.html.twig',
            [
                'vendorName'                    => $nas->getVendorName(),
                'template'                      => $template,
                'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                'login_form'                    => $loginForm,
                'login_error'                   => $error,
                'signup_form'                   => $signUpForm,
                'signup_error'                  => null,
                'social_error'                  => $request->get('socialError'),
                'emailUpdate'                   => $emailUpdate,
                'bounceValidator'               => (int) $this->bounceValidatorActive,
                'google_auth_url'               => $autUrl,
                'isMobile'                      => $isMobile,
                'compatibleOpenExternalBrowser' => $compatibleOpenExternalBrowser,
                'requiredOptIn'                 => $requiredOptIn,
                'oauths'                        => $this->getOauthLoginSources($apGroupId),
                'showHubsoftAuth'               => $showHubsoftAuth,
                'hubsoftProspectIsActive'       => $hubsoftProspectIsActive,
                'hubsoftButtonColor'            => $hubsoftButtonColor,
                'hubsoftAuthButton'             => $hubsoftAuthButton,
                'showIxcAuth'                   => $showIxcAuth,
                'IxcProspectIsActive'           => $IxcProspectIsActive,
                'IxcButtonColor'                => $IxcButtonColor,
                'IxcAuthButton'                 => $IxcAuthButton,
                'activeLegalBase'               => $activeLegalBase,
                'client'                        => $client,
                'isGoogleAuth'                  => $this->isGoogleAuth()
            ]
        );
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws NasEmptyException
     * @throws \Wideti\DomainBundle\Exception\GuestNotFoundException
     */
    public function startNavigationAction(Request $request)

    {
        if ($this->session->get("isValidated")){
            $request = $this->session->get("loginform");

            $this->session->set("isValidated", false);
            $this->session->remove("loginform");
        } else {
            if ($request->getMethod() !== 'POST') {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
            }
        }

        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->getLoggedClient();
        if (!$nas) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        $browserLanguage    = 'pt_br';
        $action             = $request->get('action');
        $property           = null;
        $value              = null;

        if ($action == 'confirmation') {
            $property   = 'email';
            $value      = $request->get($property);
        } elseif ($action == 'autologin') {
            $token = $request->get('token');
            $data = (array) JWT::decode($token, $this->autoLoginSecretKey, array('HS256'));
            $property = isset($data['loginField']) ? $data['loginField'] : null;
            $value = isset($data['loginValue']) ? $data['loginValue'] : null;
        } elseif ($action == 'accessCode') {
            $property   = $request->get('loginField');
            $value      = $request->get($property);
        } elseif ($action == 'autologin') {
            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($client)
                ->withEventIdentifier(EventIdentifier::AUTOLOGIN_CLICK)
                ->withEventType(EventType::AUTOLOGIN_CLICK)
                ->withNas($nas)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);
        }

        if ($this->configurationService->get($nas, $client, 'translation') == true) {
            $browserLanguage = $this->getBrowserLanguage($request);
        }

        if (boolval($request->get('freeAccess')) === true) {
            $this->session->set('freeAccess', true);
        }

        $oneGuestDto = new OneGuestQueryDto();
        $oneGuestDto->setProperty($property);
        $oneGuestDto->setValue($value);
        $guest = $this->guestService->getOneGuest($oneGuestDto);


        if ($guest !== null) {
            if ($guest->getStatus() == Guest::STATUS_INACTIVE) {
                $template = $this->templateService->templateSettings($this->session->get('campaignId'));

                return $this->render(
                    'FrontendBundle:SignIn:signInInactive.html.twig',
                    [
                        'guest'       => $guest,
                        'template'    => $template,
                        'templateCSS' => $template->getBackgroundCSSConfiguration()
                    ]
                );
            }

            if ($guest->getStatus() == Guest::STATUS_BLOCKED) {
                $template = $this->templateService->templateSettings($this->session->get('campaignId'));

                return $this->render(
                    'FrontendBundle:SignIn:signInBlock.html.twig',
                    [
                        'guest'       => $guest,
                        'template'    => $template,
                        'templateCSS' => $template->getBackgroundCSSConfiguration()
                    ]
                );
            }

            if ($action == 'autologin') {
                $accessCodeOwner = $this->session->get('accessCodeOwner', 0);
                if ($accessCodeOwner > 0 && $accessCodeOwner != $guest->getMysql()) {
                    $template = $this->templateService->templateSettings($this->session->get('campaignId'));
                    return $this->render(
                        'FrontendBundle:TwoFactorAuth:invalidAccessCode.html.twig',
                        [
                            'guest'       => $guest,
                            'template'    => $template,
                            'templateCSS' => $template->getBackgroundCSSConfiguration()
                        ]
                    );
                }
                $this->eventDispatcher->dispatch(
                    'core.event.authentication',
                    new AuthenticationEvent($guest, 'autologin')
                );
            }

            if ($this->twoFactorAuthAccessCode->isModuleActive() && !$this->session->get('pre-login')) {
                $accessCodeControlRepo = $this->em->getRepository('DomainBundle:AccessCodeControl');
                $acControl = $accessCodeControlRepo->findByGuestId($guest->getMysql());

                if ($acControl) {
                    if ($this->expirationTime->expiredAccessCode($client, $guest)) {
                        $acControl->setAlreadyUsedAccessCode(false);
                        $acControl->setHasToUseAccessCode(true);

                        $this->session->set('gm', $guest->getMysql());
                        $this->session->set('loginform', $request);
                        $this->session->set('autoLogin', true);

                        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                            'frontend_two_factor_auth_accesscode',
                            [
                                '_locale' => $request->get('_locale'),
                                'step_confirmation' => AccessCode::STEP_SIGNIN,
                                'step' => AccessCode::STEP_SOCIAL,
                            ]
                        ));
                    }
                }
            }
            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO){
                $traceHeaders = TracerHeaders::from($request);
                $consent = $this->getConsentGateway->get($client,'pt_BR', $traceHeaders);
                $signature = $this->listSignatureGateway->get($guest, $consent, 'pt_BR', $traceHeaders);

                $template = $this->templateService->templateSettings($this->session->get('campaignId'));

                if (!is_null($signature->getError()) && $signature->getError()->getCode() == 404){
                    return $this->controllerHelper
                        ->redirect($this->controllerHelper->generateUrl(
                            'frontend_consent_sign',
                            [
                                'guest'=>$guest->getMysql()
                            ]
                        ));
                }

                if ($signature->getStatus() == "REVOKED") {
                    return $this->render(
                        'FrontendBundle:SignIn:signInInactive.html.twig',
                        [
                            'guest'       => $guest,
                            'template'    => $template,
                            'templateCSS' => $template->getBackgroundCSSConfiguration()
                        ]
                    );
                }
            }

            return $this->nasService->process($guest, $nas);
        }

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl(
                'frontend_index',
                [
                    '_locale' => $browserLanguage
                ]
            )
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function termsOfUseAction(Request $request)
    {
        $identifier = '';
        $wspotNas   = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($wspotNas) {
            $identifier = $wspotNas->getAccessPointMacAddress();
        }

        $config = $this->configurationService->getByIdentifierOrDefault($identifier, $this->getLoggedClient());

        if (!$request->get('nas')) {
            $this->clientSelectorService->define($request->getHost());
        }

        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));
        $terms      = $config['terms_pt_br'];

        if ($request->get('_locale') == 'en') {
            $terms = $config['terms_en'];
        } elseif ($request->get('_locale') == 'es') {
            $terms = $config['terms_es'];
        }

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::AGREEMENT_TERM_VIEW)
            ->withEventType(EventType::AGREEMENT_TERM)
            ->withNas($request->get('nas'))
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:General:terms.html.twig',
            [
                'config'      => $config,
                'template'    => $template,
                'templateCSS' => $template->getBackgroundCSSConfiguration(),
                'terms'       => $terms
            ]
        );
    }

    public function consentTermAction(Request $request)
    {
        $locale = LanguageHelper::convertLocaleToLanguage($request->get('_locale'));
        $client = $this->getLoggedClient();

		$traceHeaders = TracerHeaders::from($request);
        $consent = $this->getConsentGateway->get($client, $locale, $traceHeaders);

        $identifier = '';
        $wspotNas   = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($wspotNas) {
            $identifier = $wspotNas->getAccessPointMacAddress();
        }

        $config = $this->configurationService->getByIdentifierOrDefault($identifier, $this->getLoggedClient());

        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));


        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::AGREEMENT_TERM_VIEW)
            ->withEventType(EventType::AGREEMENT_TERM)
            ->withNas($wspotNas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();
        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:General:consentTerm.html.twig',
            [
                'config'      => $config,
                'template'    => $template,
                'templateCSS' => $template->getBackgroundCSSConfiguration(),
                'formUrl'     => "https://docs.google.com/forms/d/e/1FAIpQLScPOk-0hvrmNj01wk2po0DjfJsIQ8CZLOLYffE2idjNYmx5Ag/viewform",
                'consent'     => $consent
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkFieldExists(Request $request)
    {
        $fields = $request->get('field');

        foreach ($fields as $field) {
            $check = $this->mongo
                ->getRepository('DomainBundle:CustomFields\Field')
                ->findOneBy([
                    'identifier' => $field
                ]);

            if ($check) {
                return new JsonResponse(true, 200);
            }
        }

        return new JsonResponse(false, 200);
    }

    /**
     * @return JsonResponse
     */
    public function loadCitiesAction()
    {
        $cities = $this->em
            ->getRepository('DomainBundle:City')
            ->findAll();

        $fields = [];

        foreach ($cities as $city) {
            $fields[] = $city->getName();
        }

        return new JsonResponse($fields, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function countViewAction(Request $request)
    {
        $this->prepareCampaignView($request, CampaignViews::STEP_PRE);
        return new JsonResponse(['success' => true]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function countViewPostAction(Request $request)
    {
        $this->prepareCampaignView($request, CampaignViews::STEP_POS);
        return new JsonResponse(['success' => true]);
    }

    /**
     * @param Request $request
     * @param $type
     */
    public function prepareCampaignView(Request $request, $type)
    {
        if ($this->campaignService->getById($request->get('id'))) {
            $campaignViewsDto = new CampaignViewsDto();

            $campaignViewsDto
	            ->setCampaign($request->get('id'))
                ->setType($type)
	            ->setGuestId(($request->get('guestId') !== 'null') ? (int) $request->get('guestId') : null)
                ->setGuestMacAddress(($request->get('guestMacAddress') !== 'null') ? $request->get('guestMacAddress') : null)
                ->setAccessPoint(($request->get('accessPoint') !== 'null') ? $request->get('accessPoint') : null);

            $this->campaignService->saveCampaignView($campaignViewsDto);
        }
    }

    /**
     * @param $accessPoint
     * @param Nas|null $nas
     * @param Client $client
     * @param $type
     * @return \Wideti\DomainBundle\Dto\CampaignDto|null
     */
    private function preparePreLoginCampaign($accessPoint, Nas $nas = null, Client $client, $type)
    {
        $selector = $this->campaignSelectorService->select($nas, $client, $type);
        $campaign = null;
        if ($selector) {
            $campaign = CampaignDtoHelper::convert($selector);
        }
        if ($campaign) {
            $videoSkip = $this->em
                ->getRepository("DomainBundle:VideoSkip")
                ->findOneBy([
                    'campaignId'  => $campaign->getId(),
                    'step'      => 'pre'
                ]);
            $campaign->setVideoSkip($videoSkip);


            $this->session->set('campaignId', $campaign->getId());
            $campaignHours = $campaign->getHours()->toArray();

            foreach ($campaignHours as $campaignHour) {
                $startTime  = $campaignHour->getStartTime();
                $endTime    = $campaignHour->getEndTime();
                $apTimezone = $accessPoint ? $accessPoint->getTimezone() : TimezoneService::DEFAULT_TIMEZONE;
                $now        = Carbon::now(new \DateTimeZone($apTimezone));
                $hourFrom   = Carbon::createFromTimeString($startTime)->setTimezone($apTimezone);
                $hourTo     = Carbon::createFromTimeString($endTime)->setTimezone($apTimezone);
                return ($now->gte($hourFrom) && $now->lte($hourTo) ? $campaign : null );
            }
        }

        $this->session->set('campaignId', null);
        return null;
    }

    private function removeCache()
    {
        $this->session->remove('guest');
        $this->session->remove('fb_token');
        $this->session->remove('edit');
        $this->session->remove('freeAccess');
        $this->session->remove('accessCodeDto');
        $this->session->remove('twoFactorLoginVerified');
        $this->session->remove('twoFactorSignupVerified');
        $this->session->remove('guestId');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registerCallToAction(Request $request)
    {
        $this->createAccessDataService->save($request);
        $this->setCallToActionUrlOnSession(urldecode($request->get("url")));
        return new JsonResponse(['redirectUrl' => urldecode($request->get("url"))]);
    }

    /**
     * @param $url
     */
    private function setCallToActionUrlOnSession($url)
    {
        $this->session->set("redirectUrl", $url);
        $this->session->set("callToActionUrlIsSet", true);
    }

    /**
     * @param $userAgent
     * @return string|bool Return false|"android-wv"|"android"|"ios-wv"|"ios"
     */
    private function isMobileWebView()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $iPhoneBrowser  = strpos($userAgent, "iPhone");
        $iPadBrowser    = strpos($userAgent, "iPad");
        $AndroidBrowser = strpos($userAgent, "Android");
        if (in_array('HTTP_X_REQUESTED_WITH', $_SERVER) !== false) {
            $AndroidApp = $_SERVER['HTTP_X_REQUESTED_WITH'] == "com.company.app";
        } else if (strpos($userAgent, "Android") !== false && strpos($userAgent, "wv") !== false) {
            $AndroidApp = true;
        } else {
            $AndroidApp = false;
        }
        $iOSApp = (strpos($userAgent, 'Mobile/') !== false) && (strpos($userAgent, 'Safari/') == false);
        if ($AndroidApp) {
            return "android-wv";
        }
        else if ($AndroidBrowser) {
            return "android";
        }
        else if ($iOSApp) {
            return "ios-wv";
        }
        else if($iPhoneBrowser || $iPadBrowser) {
            return "ios";
        }
        return false;
    }

    /**
     * @param string|bool $isMobile
     * @param null|string $userAgent
     * @return bool
     */
    private function compatibleOpenExternalBrowser($isMobile)
    {
        if (!$isMobile) {
            return false;
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        $keywords = [
            'Android; 4', 'Android 4',
            'Android; 5', 'Android 5',
            'Android; 6', 'Android 6',
            'Android; 7', 'Android 7',
            'Android; 8', 'Android 8',
            'Android; 9', 'Android 9',
            'Android; 10', 'Android 10'
        ];

        $compatible = true;
        foreach ($keywords as $keyword) {
            if (strpos($userAgent, $keyword) !== false) {
                $compatible = false;
                break;
            }
        }
        return $compatible;
    }

    private function getOauthLoginSources($apGroupId = null)
    {
        $clientDomain = $this->getLoggedClient()->getDomain();

        $queryBuilder = $this->em->getRepository("DomainBundle:OAuthLogin")->createQueryBuilder('o')
            ->where('o.domain = :domain')
            ->setParameter('domain', $clientDomain);

        if ($apGroupId) {
            $queryBuilder->andWhere('o.group IN (:groups) OR o.group IS NULL')
                ->setParameter('groups',$apGroupId); 
        }

        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }

    private function isGoogleAuth()
    {
        $oauth_resource = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy([
            'domain' => $this->getLoggedClient()->getDomain(),
            'url' => "https://accounts.google.com/o/oauth2/auth"
        ]);
        if ($oauth_resource) {
            return true;
        }
        return false;
    }

    public function activeJsAction(Request $request)
    {
        $identifier = '';
        $wspotNas   = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($wspotNas) {
            $identifier = $wspotNas->getAccessPointMacAddress();
        }

        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::JAVASCRIPT_DISABLED)
            ->withEventType(EventType::JAVASCRIPT_DISABLED)
            ->withNas($wspotNas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:SignIn:activeJavascript.html.twig',
            [
                'template'    => $template
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function authorizeErrorUrlAction(Request $request)   
    {
        if ($request->isMethod('GET')) {
            $token = $this->session->get("authorizeErrorUrlToken");
            $username = $request->get("username");
            $password_policy = $request->get("password_policy");
            $guest_mac_address = $request->get("guest_mac_address");
            $unifi_site = $request->get("unifi_site");
            $ap_mac_address = $request->get("ap_mac_address");
            $redirect_url = $request->get("redirect_url");
            $ap_ssid = $request->get("ap_ssid");
            $panel_domain = $request->get("panel_domain");
            $login_url = $request->get("login_url");
            $authorize_error_url = $request->get("authorize_error_url");
            $authorize_error = $request->get("authorize_error") == '1' ? true : false;
            if (!$authorize_error) {
                $this->session->set('unifiAuthorized', true);
            }
            $formData = [
                '_token' => $token,
                'username' => $username,
                'password_policy' => $password_policy,
                'guest_mac_address' => $guest_mac_address,
                'unifi_site' => $unifi_site,
                'ap_mac_address' => $ap_mac_address,
                'redirect_url' => $redirect_url,
                'ap_ssid' => $ap_ssid,
                'panel_domain' => $panel_domain,
                'login_url' => $login_url,
                'authorize_error_url' => $authorize_error_url
            ];

            $response = $this->render(
                'FrontendBundle:SignIn:authorizeErrorUrl.html.twig',
                [
                    'template' => "",
                    'formData' => $formData,
                    'authorizeError' => $authorize_error
                ]
            );

            // Adicione cabeçalhos CORS para permitir apenas google.com
            $response->headers->set('Access-Control-Allow-Origin', 'https://www.google.com');
            $response->headers->set('Access-Control-Allow-Methods', 'HEAD');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

            return $response;
        }
        
        // O método é POST
        $data = json_decode($request->getContent(), true);
        $loggerData = isset($data['logger']) ? $data['logger'] : null;
        if ($loggerData) {
            $client = $this->getLoggedClient();
            $domain = $client->getDomain();
            $data = [
                "getGoogle" => $loggerData['getGoogle'],
                "pageReload" => $loggerData['pageReload'],
                "getGoogleSuccess" => $loggerData['getGoogleSuccess'],
                "authorizeTime" => $loggerData['authorizeTime'],
                "authorizeUnifiSuccess" => $loggerData['authorizeUnifiSuccess'],
                "failAuthorizeUnifi" => $loggerData['failAuthorizeUnifi'],
                "authorizeUrl" => $loggerData['authorizeUrl']
            ];
            $this->logger->addWarning("[authorize_unifi:{$domain}]", $data);
            return new JsonResponse(['error' => false]);
        }

        $url = isset($data['url']) ? $data['url'] : null;
        if (!$url) {
            return new JsonResponse(['error' => true, 'message_error' => 'URL é necessária.', 'message_info' => ''], Response::HTTP_BAD_REQUEST);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Não seguir redirecionamentos
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return new JsonResponse(['error' => true, 'message_error' => 'Erro ao fazer a requisição.', 'message_info' => ''], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $locationHeader = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        $error = false;
        $messageError = '';
        $messageInfo = '';

        if ($locationHeader) {
            $error = strpos($locationHeader, 'authorize_error=1') !== false;

            // Extrai parâmetros da URL
            parse_str(parse_url($locationHeader, PHP_URL_QUERY), $queryParams);
            $messageError = isset($queryParams['msg_error']) ? $queryParams['msg_error'] : '';
            $messageInfo = isset($queryParams['msg_info']) ? $queryParams['msg_info'] : '';
        }
        if (!$error) {
            $this->session->set('unifiAuthorized', true);
        }
        return new JsonResponse([
            'error' => $error,
            'message_error' => $messageError,
            'message_info' => $messageInfo,
        ]);
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function previewDoneAction(Request $request)   
    {
        $apIdentifier = !empty($request->get('ap')) ? $request->get('ap') : null;
        $campaignId   = !empty($this->session->get('campaignId')) ? $this->session->get('campaignId') : null;
        $client       = $this->session->get('wspotClient');
        $this->session->set("isWhiteLabel", $client->isWhiteLabel());
        $template = $this->templateService->getTemplateBy($client, $apIdentifier, $campaignId);
        $request_url_origin = $this->session->get('request_url_origin');

        return $this->render(
            'FrontendBundle:SignIn:previewDone.html.twig',
            [
                'template'                      => $template,
                'templateCSS'                   => $template->getBackgroundCSSConfiguration(),
                'request_url_origin'            => $request_url_origin
            ]
        );
    }
}
