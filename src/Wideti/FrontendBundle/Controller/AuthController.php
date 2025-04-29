<?php
namespace Wideti\FrontendBundle\Controller;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Dto\OneGuestQueryDto;
use Wideti\DomainBundle\Dto\SignInStatusDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeControl;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Consents;
use Wideti\DomainBundle\Entity\GuestAuthCode;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Event\AuthenticationEvent;
use Wideti\DomainBundle\Exception\ConsentErrorException;
use Wideti\DomainBundle\Exception\GuestNotFoundException;
use Wideti\DomainBundle\Exception\InvalidEmailException;
use Wideti\DomainBundle\Exception\InvalidSmsPhoneNumberException;
use Wideti\DomainBundle\Exception\MongoDuplicateKeyRegisterException;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Gateways\Consents\Signature;
use Wideti\DomainBundle\Helpers\AuditLogsHelper;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelperImp;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\FacebookRedirectLikeAndShareHelper;
use Wideti\DomainBundle\Helpers\FieldsHelper;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeServiceImp;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditLogsService;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Consent\ConsentRequest;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\GuestNotification\Base\NotificationType;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsService;
use Wideti\DomainBundle\Service\GuestSocial\GuestSocialServiceAware;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\SignUp\SignUpServiceAware;
use Wideti\DomainBundle\Service\SignIn\SignInServiceAware;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Service\TwoFactorAuth\TwoFactorAuthService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Form\SignUpConfirmationType;
use Wideti\FrontendBundle\Form\SocialMediaRegistrationType;
use Wideti\FrontendBundle\Form\ForgotPasswordType;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\ExpirationTime\ExpirationTimeImp;

/**
 * Class AuthController
 * @package Wideti\FrontendBundle\Controller
 */
class AuthController implements NasControllerHandler
{
    use EntityManagerAware;
    use MongoAware;
    use SessionAware;
    use FormAware;
    use RouterServiceAware;
    use TwigAware;
    use GuestServiceAware;
    use GuestSocialServiceAware;
    use LoggerAware;
    use TranslatorAware;
    use SignInServiceAware;
    use SignUpServiceAware;
    use EventDispatcherAware;
    use NasServiceAware;
    use TemplateAware;
    use CustomFieldsAware;
    use GroupServiceAware;

    /**
     * @var ControllerHelperImp $controllerHelperImp
     */
    private $controllerHelperImp;
    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var SmsService
     */
    private $smsService;
    /**
     * @var TwoFactorAuthService
     */
    private $twoFactorAuthAccessCode;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var AccessCodeServiceImp
     */
    private $accessCodeService;
    /**
     * @var GuestToAccountingProcessor
     */
    private $accountingProcessor;

    private $bounceValidatorActive;

    /**
     * @var EventLoggerManager
     */
    private $logManager;
    /**
     * @var ConsentRequest
     */
    private $consent;

    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * @var ExpirationTimeImp
     */
    private $expirationTime;

    /**
     * AuthController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param SmsService $smsService
     * @param TwoFactorAuthService $twoFactorAuthAccessCode
     * @param CacheServiceImp $cacheService
     * @param AccessCodeServiceImp $accessCodeService
     * @param GuestToAccountingProcessor $accountingProcessor
     * @param $bounceValidatorActive
     * @param EventLoggerManager $logManager
     * @param ConsentRequest $consent
     * @param GetConsentGateway $getConsentGateway;
     * @param ExpirationTimeImp $expirationTime;
     */
    public function __construct(
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        SmsService $smsService,
        TwoFactorAuthService $twoFactorAuthAccessCode,
        CacheServiceImp $cacheService,
        AccessCodeServiceImp $accessCodeService,
        GuestToAccountingProcessor $accountingProcessor,
        $bounceValidatorActive,
        EventLoggerManager $logManager,
        ConsentRequest $consent,
		GetConsentGateway $getConsentGateway,
        LegalBaseManagerService $legalBaseManagerService,
        Auditor $auditor,
        ExpirationTimeImp $expirationTime
    ) {
        $this->configurationService     = $configurationService;
        $this->controllerHelper         = $controllerHelper;
        $this->bounceValidatorActive    = $bounceValidatorActive;
        $this->smsService               = $smsService;
        $this->twoFactorAuthAccessCode  = $twoFactorAuthAccessCode;
        $this->cacheService             = $cacheService;
        $this->accessCodeService        = $accessCodeService;
        $this->accountingProcessor      = $accountingProcessor;
        $this->logManager               = $logManager;
        $this->consent                  = $consent;
        $this->getConsentGateway        = $getConsentGateway;
        $this->legalBaseManager         = $legalBaseManagerService;
        $this->auditor                  = $auditor;
        $this->expirationTime           = $expirationTime;
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse|Response|\Wideti\WebFrameworkBundle\Service\Router\Response
     * @throws GuestNotFoundException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     */
    public function signInAction(Request $request)
    {
        $client     = $this->getLoggedClient();
        $nas        = $this->session->get(Nas::NAS_SESSION_KEY);
        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));
        $loginForm  = $this->controllerHelper->signInForm($request);

        $loginForm->handleRequest($request);
        $error = "";

        if ($this->session->get("isValidated")){
            $request    = $this->session->get("loginform");
            $loginForm  = $this->controllerHelper->signInForm($request);
            $loginForm ->handleRequest($request);

            $this->session->remove("loginform");
            $this->session->set("isValidated", false);
        } else{
            if ($request->getMethod() !== 'POST') {
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
            }
        }

        if ($loginForm->isValid()) {
            $accessData = $loginForm->getData();
            $accessData = $this->formatLowerCase($accessData);

            try {
                $doLogin = $this->signInService->doSignIn($accessData, $nas);
            } catch (\Exception $ex) {
                return $this->routerService->forward(
                    'wspot.frontend.controller.frontend:indexAction',
                    [
                        'template'   => $template,
                        'loginForm'  => $loginForm->createView(),
                        'error'      => $ex->getMessage(),
                        'autoLogin'  => 0
                    ]
                );
            }

            $doLoginStatus = $doLogin->getStatus();
            $loginField  = $accessData->getProperties();
            $oneGuestDto = new OneGuestQueryDto();
            $oneGuestDto->setProperty(key($loginField));
            $oneGuestDto->setValue(array_values($loginField)[0]);
            $guest = $this->guestService->getOneGuest($oneGuestDto);

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
            if ($doLoginStatus == SignInStatusDto::EMAIL_IS_INVALID && $guest->getStatus() == Guest::STATUS_INACTIVE) {

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($this->getLoggedClient())
                    ->withEventIdentifier(EventIdentifier::UPDATE_EMAIL)
                    ->withEventType(EventType::UPDATE_EMAIL)
                    ->withNas($nas)
                    ->withRequest($request)
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl(
                        'frontend_email_update',
                        [
                            'fields[]'  => 'email',
                            'guest'     => $guest
                        ]
                    )
                );
            }

            if ($doLoginStatus == SignInStatusDto::SIGNIN_WITH_CONFIRMATION_BLOCKED) {
                return $this->render(
                    'FrontendBundle:SignIn:signInBlock.html.twig',
                    [
                        'guest'    => $guest,
                        'template' => $this->templateService->templateSettings(
                            $this->session->get('campaignId')
                        )
                    ]
                );
            }

            if ($doLoginStatus == SignInStatusDto::SIGNIN_WITH_CONFIRMATION) {
                $confirmationForm = $this->signUpConfirmationForm($guest);
                $confirmationForm->handleRequest($request);

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($this->getLoggedClient())
                    ->withEventIdentifier(EventIdentifier::CONFIRMATION_SIGNUP)
                    ->withEventType(EventType::CONFIRMATION_SIGNUP)
                    ->withNas($nas)
                    ->withRequest($request)
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);
                return $this->render(
                    'FrontendBundle:SignUp:signUpConfirmation.html.twig',
                    [
                        'guest'    => $guest,
                        'template' => $this->templateService->templateSettings(
                            $this->session->get('campaignId')
                        ),
                        'form'     => $confirmationForm->createView(),
                        'type'     => $this->session->get('confirmationType'),
                        'resend'   => false
                    ]
                );
            }


            if ($doLoginStatus == SignInStatusDto::SIGNIN_SUCCESS) {
                if ($guest->getResetPassword()) {
                    $hasDocument    = $this->mongo->getRepository('DomainBundle:CustomFields\Field')->hasField('document');
                    $hasEmail       = $this->customFieldsService->getFieldByNameType('email');
                    $form = $this->controllerHelper->createForm(
                        ForgotPasswordType::class,
                        $guest,
                        [
                            'action'    => $this->controllerHelper->generateUrl(
                                'frontend_recovery_password',
                                [
                                    'guestId' => $guest->getMysql()
                                ]
                            ),
                            'method'    => 'POST',
                            'attr'      => [
                                'step'       => 'pwd-only',
                            ]
                        ]
                    );

                    return $this->render(
                        'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                        [
                            'wspotNas'      => $nas,
                            'template'      => $template,
                            'form'          => $form->createView(),
                            'error'         => $error,
                            'hasDocument'   => $hasDocument,
                            'hasEmail'      => $hasEmail,
                            'step'          => 'pwd-only'
                        ]
                    );
                }
                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($this->getLoggedClient())
                    ->withEventIdentifier(EventIdentifier::LOGIN_BY_FORM)
                    ->withEventType(EventType::LOGIN_BY_FORM)
                    ->withNas($nas)
                    ->withRequest($request)
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);
                if ($this->twoFactorAuthAccessCode->isModuleActive()  && !$this->session->get('pre-login')) {
                    $accessCodeControlRepo = $this->em->getRepository('DomainBundle:AccessCodeControl');
                    $acControl = $accessCodeControlRepo->findByGuestId($guest->getMysql());
                    if ($acControl) {
                        $hasToUseAccessCode = $acControl->getHasToUseAccessCode();
                        $alreadyUsedAccessCode = $acControl->getAlreadyUsedAccessCode();

                        if (($hasToUseAccessCode && !$alreadyUsedAccessCode) || !$guest->isHasSecurityAnswer()) {
                            $this->session->set('gm', $guest->getMysql());
                            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                                'frontend_two_factor_auth_accesscode',
                                [
                                    '_locale'              => $request->get('_locale'),
                                    'step'                 => AccessCode::STEP_SIGNUP,
                                    'step_confirmation'    => AccessCode::STEP_SIGNUP_CONFIRMATION
                                ]
                            ));
                        }

                        if ($this->expirationTime->expiredAccessCode($client, $guest)) {
                            $acControl->setAlreadyUsedAccessCode(false);
                            $acControl->setHasToUseAccessCode(true);

                            $this->session->set('gm', $guest->getMysql());
                            $this->session->set('loginform', $request);

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
                    else {

                        $this->session->set('gm', $guest->getMysql());
                        $this->session->set('loginform', $request);

                        $acControl = new AccessCodeControl($client->getId(), $guest->getMysql(), true, false);
                        $this->em->persist($acControl);
                        $this->em->flush();

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

                $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
                if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
                    try {
                        $result = $this->processSignInConsentSignature($doLogin->getGuest(), $client);
                        if (!$result) {
                            return $this->controllerHelper
                                ->redirect($this->controllerHelper->generateUrl(
                                    'frontend_consent_sign',
                                    [
                                        'guest'=>$guest->getMysql()
                                    ]
                                ));
                        }
                    }catch(ConsentErrorException $e) {
                        $this->logger->addCritical($e->getMessage());
                    }
                }elseif ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::LEGITIMO_INTERESSE){
                    // Audit
                    $event = $this->auditor->newEvent();
                    $event
                        ->withClient($client->getId())
                        ->withSource(Kinds::guest(), $guest->getMysql())
                        ->withType(Events::create())
                        ->onTarget(Kinds::guest(), $guest->getMysql())
                        ->addDescription(AuditEvent::PT_BR, 'Usuário tem ciência do Legítimo Interesse definido pelo controlador.')
                        ->addDescription(AuditEvent::EN_US, 'The user is aware of the legitimate interest defined by the controller.')
                        ->addDescription(AuditEvent::ES_ES, 'El usuario es consciente del interés legítimo definido por el responsable del tratamiento.');
                    $this->auditor->push($event);
                }

                return $this->nasService->process($doLogin->getGuest(), $nas);
            }
        }

        return $this->routerService->forward(
            'wspot.frontend.controller.frontend:indexAction',
            [
                'template'   => $template,
                'loginForm'  => $loginForm->createView(),
                'error'      => $error,
                'autoLogin'  => 0
            ]
        );
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse|\Wideti\WebFrameworkBundle\Service\Router\Response
     * @throws GuestNotFoundException
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     */
    public function signUpAction(Request $request)
    {
    	$nas        = $this->session->get(Nas::NAS_SESSION_KEY);
        $client     = $this->getLoggedClient();
        $guestId    = $request->getSession()->get('guestId');

        if ($request->getMethod() !== 'POST' && !$this->session->get('twoFactorSignupVerified')) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }
        $this->verifyPocLimitSMS($nas);

        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));

        $apGroupId = $this->configurationService-> getApGroupId($nas, $client);
        /**
         * @var Form $signUpForm
         */
        $signUpForm = $this->controllerHelper->signUpForm(
            $request,
            $this->configurationService->get($nas, $client, 'authorize_email'),
            $apGroupId
        );

        $signUpForm->handleRequest($request);

        if ($signUpForm->isValid()) {
            $accessCodeOwner = $this->session->get('accessCodeOwner', 0);
            $guest          = $signUpForm->getData();
            if ($accessCodeOwner > 0) {
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
            
            if (!is_null($guest->get("email"))) {
               $guest->addProperty('email', strtolower($guest->get('email')));
            }
            $emailValidate  = $signUpForm->get('emailValidate')->getData();
            $locale         = $request->get('_locale');
            $this->session->set('locale', $locale);

            $mac  = $nas->getGuestDeviceMacAddress();
            $macAp = $nas->getAccessPointMacAddress();
            $loginFieldIdentifier = $this->customFieldsService->getLoginFieldIdentifier();
            $this->formatMultipleChoiceFields($guest);
            $property_no_user_fields = 'mac_address';
            if ($loginFieldIdentifier == $property_no_user_fields) {
                $guest->setLoginField($property_no_user_fields);
                $guest->addProperty($property_no_user_fields, $mac);
            }

            if ($this->configurationService->isUniqueMacEnabled($client, $macAp)) {
                if ($this->configurationService->isMacAlreadyRegistered($mac)) {
                    $this->session->getFlashBag()->set('error', $this->translator->transChoice('wspot.signup_page.mac_registered_error', 1));
                    return $this->routerService->forward(
                        'wspot.frontend.controller.frontend:indexAction',
                        [
                            'template'   => $template,
                            'signUpForm' => $signUpForm->createView(),
                            'autoLogin'  => 0
                        ]
                    );
                }
            }

            if (!$emailValidate) {
                $signUpForm->get('properties')['email']->addError(
                    new FormError($this->translator->trans('wspot.signup_page.field_valid_domain_email'))
                );

                return $this->routerService->forward(
                    'wspot.frontend.controller.frontend:indexAction',
                    [
                        'template'   => $template,
                        'signUpForm' => $signUpForm->createView(),
                        'autoLogin'  => 0
                    ]
                );
            }

            try {
                FieldsHelper::transformPhoneAndMobileGuest($guest, $signUpForm);
                $signUpStatus   = $this->signUpService->doSignUp($guest, $nas, $locale, $emailValidate);
                $guest          = $signUpStatus->getCreatedGuest();


                $isOn = $request->get("wspot_signup_consent_term");

                $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
                if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO){
                    try {
                        $traceHeaders = TracerHeaders::from($request);
                        $this->processSignUpConsentSignature($guest, $client, $nas, $isOn, $traceHeaders);
                    } catch(ConsentErrorException $e) {
                        $this->logger->addCritical($e->getMessage());
                    }
                }elseif ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::LEGITIMO_INTERESSE){
                    // Audit
                    $event = $this->auditor->newEvent();
                    $event
                        ->withClient($client->getId())
                        ->withSource(Kinds::guest(), $guest->getMysql())
                        ->withType(Events::create())
                        ->onTarget(Kinds::guest(), $guest->getMysql())
                        ->addDescription(AuditEvent::PT_BR, 'Usuário tem ciência do Legítimo Interesse definido pelo controlador.')
                        ->addDescription(AuditEvent::EN_US, 'The user is aware of the legitimate interest defined by the controller.')
                        ->addDescription(AuditEvent::ES_ES, 'El usuario es consciente del interés legítimo definido por el responsable del tratamiento.');
                    $this->auditor->push($event);
                }

            } catch (InvalidEmailException $e) {
                $form = $this->getServerSideErrorResponse($signUpForm, $e->getMessage());
                return $this->routerService->forward(
                    'wspot.frontend.controller.frontend:indexAction',
                    [
                        'template'   => $template,
                        'signUpForm' => $form->createView(),
                        'autoLogin'  => 0
                    ]
                );
            } catch (UniqueFieldException $e) {
                $field = $this->customFieldsService->getFieldByNameType($e->getMessage());
                return $this->getUniqueFieldErrorResponse($signUpForm, $template, $field);
            } catch (InvalidSmsPhoneNumberException $e) {
                $this->session->set('edit', $guest->getMysql());
                return $this->getInvalidPhoneSmsErrorResponse($guest, 'register');
            } catch(MongoDuplicateKeyRegisterException $e) {
                $duplicatedFieldString = $this->stringBetweenTwoString(
                    $e->getMessage(),
                    "index:",
                    "}"
                );
                $customFieldStringInExplodedArray = explode("_",$duplicatedFieldString);
                $field = $this->customFieldsService->getFieldByNameType($customFieldStringInExplodedArray[1]);
                if ($field) {
                    return $this->getUniqueFieldErrorResponse($signUpForm, $template, $field);
                }

                $form = $this->getServerSideErrorResponse($signUpForm,
                    $this->translator->trans('wspot.login_page.duplicated_register'));

                return $this->routerService->forward(
                    'wspot.frontend.controller.frontend:indexAction',
                    [
                        'template'   => $template,
                        'signUpForm' => $form->createView(),
                        'autoLogin'  => 0
                    ]
                );

            } catch (\Exception $e) {

                $form = $this->getServerSideErrorResponse($signUpForm, $e->getMessage());

                return $this->routerService->forward(
                    'wspot.frontend.controller.frontend:indexAction',
                    [
                        'template'   => $template,
                        'signUpForm' => $form->createView(),
                        'autoLogin'  => 0
                    ]
                );
            }

            $this->session->set('guestId', $guest->getId());

            if ($signUpStatus->getConfirmation()->isConfirmationNeeded()) {
                return $this->controllerHelper->redirectToRoute(
                    'frontend_signup_confirmation_action',
                    [
                        'guest'  => $guest->getMysql(),
                        'resend' => false
                    ]
                );
            }

            if ($this->twoFactorAuthAccessCode->isModuleActive()
                && $this->session->get('twoFactorLoginVerified')
                && !$this->session->get('twoFactorSignupVerified')) {
                $accessCodeParams = $this->session->get('accessCodeDto')->getAccessCodeParams();
                if (!$accessCodeParams || array_key_exists('step', $accessCodeParams) == AccessCode::STEP_SIGNUP) {
                    $this->session->set('step', AccessCode::STEP_SIGNUP);


                    $acControl = new AccessCodeControl($client->getId(), $guest->getMysql(), true, false);
                    $this->em->persist($acControl);
                    $this->em->flush();

                    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                        'frontend_two_factor_auth_accesscode',
                        [
                            '_locale' => $request->get('_locale'),
                            'step'    => AccessCode::STEP_SIGNUP
                        ]
                    ));
                }
            }

            $this->eventDispatcher->dispatch('core.event.authentication', new AuthenticationEvent($guest, 'form'));

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($this->getLoggedClient())
                ->withEventIdentifier(EventIdentifier::REGISTER_BY_FORM)
                ->withEventType(EventType::REGISTER_BY_FORM)
                ->withNas($nas)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->nasService->process($guest, $nas);
        }

        if ($guestId) {
            $guest = $this->mongo->getRepository('DomainBundle:Guest\Guest')->find($guestId);
            $this->eventDispatcher->dispatch('core.event.authentication', new AuthenticationEvent($guest, 'form'));


            return $this->nasService->process($guest, $nas);
        }

        return $this->routerService->forward(
            'wspot.frontend.controller.frontend:indexAction',
            [
                'template'   => $template,
                'signUpForm' => $signUpForm->createView()
            ]
        );
    }

    private function formatMultipleChoiceFields($guest) {
        $fields = $this->customFieldsService->getCustomFields();
        $multipleChoiceFields = [];
        foreach ($fields as $field) {
            if ($field->getType() == "multiple_choice") {
                $multipleChoiceFields[] = $field->getIdentifier();
            }
        }
        if (!$multipleChoiceFields) {
          return ;
        }
        $properties = $guest->getProperties();
        foreach ($multipleChoiceFields as $multipleChoiceField) {
            $properties[$multipleChoiceField] = implode(' - ', $properties[$multipleChoiceField]);
        }
        $guest->setProperties($properties);
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws GuestNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     */
    public function signUpConfirmationAction(Request $request)
    {
        if ((int)$request->get('guest') == 0 || is_null((int)$request->get('guest'))) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        /** @var $entity Guest **/
        $entity = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql((int)$request->get('guest'));

        if (!$entity)
            throw new GuestNotFoundException("GuestNotFoundException on signUpConfirmationAction");

        $client             = $this->getLoggedClient();
        $confirmationType   = 'sms';
        $actionType         = $request->get('type') ?  $request->get('type') : 'register';
        $nas                = $this->session->get(Nas::NAS_SESSION_KEY);

        if (!$nas && !$entity->getNasVendor()) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        $confirmationForm = $this->signUpConfirmationForm($entity);

        $confirmationForm->handleRequest($request);

        if (!$this->configurationService->get($nas, $client, 'confirmation_sms') &&
            $this->configurationService->get($nas, $client, 'confirmation_email')) {
            $confirmationType = 'email';
        }

        $authcode_validated = $this->session->get('authcode-validated');
        if (($authcode_validated && $authcode_validated['valid_until'] > time()) || $confirmationForm->isValid()) {
            $this->session->set('authcode-validated', [
                'valid_until' => time() + 10, # 10 segundos
            ]);

            $this->guestService->confirm($entity, $nas);

            $authCode = $this->em->getRepository("DomainBundle:GuestAuthCode")
                ->findOneByGuest($entity->getMysql());

            if ($authCode != null) {
                $this->em->remove($authCode);
                $this->em->flush();
            }

            return $this->nasService->process($entity, $nas);
        }

        $sendSms = "";
        if (!is_null($this->session->get(SmsService::SEND_SMS_FAIL))) {
            $sendSms = SmsService::SEND_SMS_FAIL_VALUE;
        }

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::CONFIRMATION_SIGNUP)
            ->withEventType(EventType::CONFIRMATION_SIGNUP)
            ->withNas($nas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(["send_sms" => $sendSms])
            ->build();

        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:SignUp:signUpConfirmation.html.twig',
            [
                'locale'   => $this->session->get('locale'),
                'guest'    => $entity,
                'template' => $this->templateService->templateSettings($this->session->get('campaignId')),
                'form'     => $confirmationForm->createView(),
                'type'     => $confirmationType,
                'action'   => $actionType,
                'resend'   => $request->get('resend'),
                'smsSend'  => $sendSms
            ]
        );
    }

    /**
     * @param Guest $guest
     * @return FormInterface
     */
    public function signUpConfirmationForm(Guest $guest)
    {
        return $this->controllerHelper->createForm(
            SignUpConfirmationType::class,
            $guest,
            [
                'action' => $this->controllerHelper->generateUrl(
                    'frontend_signup_confirmation_action',
                    [
                        'guest' => $guest->getMysql()
                    ]
                ),
                'method' => 'POST'
            ]
        );
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws GuestNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
     */
    public function confirmUrlAction(Request $request)
    {
        // nasFactory não pode existir, é versão antiga e pode dar erro
        if (array_key_exists('nasFactory', $request->query->all())) {
            return $this->controllerHelper->getNotFound404Response();
        }

        if (!$request->query->get('vendorName')) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        $hash       = $request->query->get('token');
        $nas        = $this->session->get(Nas::NAS_SESSION_KEY);
        $client     = $this->session->get('wspotClient');
        $fromEmail  = 'no-reply@mambowifi.com';

        /** @var GuestAuthCode $guestAuth */
        $guestAuth = $this->em
            ->getRepository('DomainBundle:GuestAuthCode')
            ->findOneByCode($hash);

        if ($guestAuth === null) {
            return $this->renderInvalidTokenView($fromEmail);
        }

        /** @var Guest $guest */
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql((int)$guestAuth->getGuest()->getId());

        if (!$guest) {
            throw new GuestNotFoundException("Guest not found: MySQL # {$guestAuth->getGuest()->getId()}");
        }

        if (!$nas) {
            $this->logger->addWarning("Nas created manually on confirmUrlAction. Guest: {$guest->getId()}");
            $nas = $this->nasService->createNasManually($request->query->get('vendorName'), $guest);
            $this->session->set(Nas::NAS_SESSION_KEY, $nas);
        }

        if (!$nas || !$request->query->get('vendorName')) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        $configMap = $this->configurationService->getByIdentifierOrDefault($nas->getAccessPointMacAddress(), $client);
        $this->configurationService->setOnSession($nas->getAccessPointMacAddress(), $configMap);
        $this->controllerHelper->setTwigGlobalVariable('config', $configMap);
        $validated = false;
        if ($request->query->get('valid') == true) {
            if ($this->session->get('confirmationValid')) {
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_redirection_url'));
            }
            $this->em->remove($guestAuth);
            $this->em->flush();

            try {
                $this->guestService->confirm($guest, $nas);
                $validated = true;
            } catch (InvalidSmsPhoneNumberException $e) {
                return $this->getInvalidPhoneSmsErrorResponse($guest, 'welcome');
            }

            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
                try {
                    $traceHeaders = TracerHeaders::from($request);
                    $this->processSignUpConfirmationConsentSignature($guest, $client, $nas, $traceHeaders);
                    $validated = true;
                } catch (ConsentErrorException $e) {
                    $validated = false;
                    $this->logger->addCritical($e->getMessage());
                }
            }
            $this->session->set('confirmationValid', 1);
            return $this->nasService->process($guest, $nas);
        }

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::WAITING_CONFIRMATION_SIGNUP)
            ->withEventType(EventType::WAITING_CONFIRMATION_SIGNUP)
            ->withNas($nas)
            ->withRequest($request)
            ->withGuest($guest)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);
        if ($validated) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_redirection_url'));
        }
        return $this->render(
            'FrontendBundle:SignUp:signUpWaitingConfirmation.html.twig',
            [
                'locale'   => $this->session->get('locale'),
                'guest'    => $guest,
                'nas'      => $nas,
                'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
            ]
        );
    }

    /**
     * @param $fromEmail
     * @return Response
     */
    private function renderInvalidTokenView($fromEmail)
    {
        return $this->render(
            'FrontendBundle:General:invalidToken.html.twig',
            [
                'template' => $this->templateService->templateSettings($this->session->get('campaignId')),
                'fromEmail' => $fromEmail
            ]
        );
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws GuestNotFoundException
     * @throws \ReflectionException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
     */
    public function guestConfirmation(Request $request)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->getLoggedClient();
        $guest = $this->mongo->getRepository('DomainBundle:Guest\Guest')
            ->findOneById($request->get('guest'));

        if (!$guest) {
            $this->logger->addError("Guest not found: ID # {$request->get('guest')}");
            $browserLanguage = $this->getBrowserLanguage($request);
            $this->session->getFlashBag()->add('error',
                "URL de acesso inválida");
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    'frontend_index',
                    [
                        '_locale' => $browserLanguage
                    ]
                )
            );
        }

        if (!$guest) {
            throw new GuestNotFoundException("Guest not found: ID # {$request->get('guest')}");
        }

        if (!$nas) {
            $this->logger->addWarning("Nas created manually on guestConfirmation. Guest: {$guest->getId()}");
            $nas = $this->nasService->createNasManually($guest->getNasVendor(), $guest);
            $this->session->set(Nas::NAS_SESSION_KEY, $nas);
        }

        if ($guest->getStatus() == Guest::STATUS_ACTIVE ||
            ($this->configurationService->get($nas, $client, 'confirmation_email') != 1
                && $this->configurationService->get($nas, $client, 'confirmation_sms') != 1)) {

            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
                try {
                    $traceHeader = TracerHeaders::from($request);
                    $this->processSignUpConfirmationConsentSignature($guest, $client, $nas, $traceHeader);
                } catch (ConsentErrorException $e) {
                    $this->logger->addCritical($e->getMessage());
                }
            }

            $oauthClientId = $this->session->get('oauthClientId');
            if ($oauthClientId) {
                $oauthRequires2FA = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy([
                    'domain' => $client->getDomain(),
                    'clientId' => $oauthClientId,
                    'twoFactorRequired' => true,
                ]);

                if ($oauthRequires2FA) {
                    $this->sendConfirmationSMS($guest, $nas);
                    return $this->controllerHelper->redirectToRoute(
                        'frontend_signup_confirmation_action',
                        [
                            'guest'  => $guest->getMysql(),
                            'resend' => false,
                            'type'   => 'login'
                        ]
                    );
                }
            }

            return $this->nasService->process($guest, $nas);
        }

	    if ($this->guestService->verifyUserBlockPerTime($guest) === true) {

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($this->getLoggedClient())
                ->withEventIdentifier(EventIdentifier::BLOCK_BY_TIME)
                ->withEventType(EventType::BLOCK_BY_TIME)
                ->withNas($nas)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->render(
                'FrontendBundle:SignIn:signInBlock.html.twig',
                [
                    'guest'    => $guest,
                    'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                ]
            );
        }

        return $this->controllerHelper->redirectToRoute(
            'frontend_signup_confirmation_action',
            [
                'guest' => $guest->getMysql(),
                'resend' => false
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
     */
    public function completeRegistrationAction(Request $request)
    {
        if (!$this->session->get(Nas::NAS_SESSION_KEY)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');

        $this->session->remove('newGuest');

        $template       = $this->templateService->templateSettings($this->session->get('campaignId'));
        $session        = $request->getSession();
        $guestData      = $session->get('guest');
        $guestInfo      = $guestData['data'];
        $facebookFields = $guestData['fields'];
        $socialType     = $guestData['social']['type'];

        if ($guestInfo === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'socialError' => $guestData['social']['type']
            ]));
        }

        $emailGuest = isset($guestInfo['email'])? $guestInfo['email'] : null;
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findLikeEmail($emailGuest);

        if (empty($guest) && isset($guestData['social']['id'])) {
            $guest = $this->mongo->getRepository('DomainBundle:Guest\Guest')
                ->findBySocialId($guestData['social']['id']);
        }

        if ($guestData) {
            $socialProperties = $request->request->get('social_media_registration')['properties'];
            if ($emailGuest == null && $socialProperties && array_key_exists('email', $socialProperties)) {
                $guestInfo['email'] = $socialProperties['email'];
            }
        }

        if ($guest != false && $guest->getStatus() == Guest::STATUS_INACTIVE) {
            return $this->render(
                'FrontendBundle:SignIn:signInInactive.html.twig',
                [
                    'guest'    => $guest,
                    'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                ]
            );
        }

        if ($guest && array_key_exists('email', $guest->getProperties()) && $guest->getEmailIsValid() == false) {
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    'frontend_email_update',
                    [
                        'fields[]'  => 'email',
                        'guest'     => $guest->getMySql()
                    ]
                )
            );
        }

        if ($guest instanceof Guest) {
        error_log("Usuário já cadastrado com ID: " . $guest->getId());

            $this->session->set('edit', $guest->getMysql());

            if (!$this->guestSocialService->verifyCredentials($guest, $guestData)) {
                $this->guestSocialService->create($guest, $guestData);
            }

            $this->accountingProcessor->process($client, $guest);

            if ($socialType == Social::FACEBOOK) {
                $this->guestSocialService->facebookFields($guest, $facebookFields);
                return $this->facebookPublish($guest, $socialType);
            }

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $guest->getId(),
                    'socialType' => $socialType
                ])
            );
        }
        error_log("Usuário não cadastrado, iniciando processo de criação com createByFrontend.");

        $guest = new Guest();

        $fieldName = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findOneBy(['identifier'=> 'name']);

        if ($fieldName) {
            $guest->addProperty('name', $guestInfo['name']);
        }

        if ($emailGuest) {
            $guest->addProperty('email', $guestInfo['email']);
        }

        $guestInfo["field_login"] = $this->customFieldsService->getLoginFieldIdentifier();
        $guest->setLocale($request->get('_locale'));

        $formFields = $this->handleFormFields($guestInfo);

        $form = $this->createCompleteRegistrationForm($guest, $formFields, 'complete_registration');

        $form->handleRequest($request);

        $requiredOptIn = (bool) $this->configurationService->get($nas, $this->getLoggedClient(), 'request_optin');

        if ($form->isSubmitted()) {
            $data       = $form->getData();
            $existUser  = $this->guestService->verifyUser($data);

            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            $consentTermAccepted = !is_null($request->get("social_media_registration_consent_term"));
            if (!$data) {

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($this->getLoggedClient())
                    ->withEventIdentifier(EventIdentifier::COMPLETE_REGISTRATION)
                    ->withEventType(EventType::COMPLETE_REGISTRATION)
                    ->withNas($nas)
                    ->withGuest($guest)
                    ->withRequest($request)
                    ->withSession($this->session)
                    ->withExtraData(["facebook_fields" => $facebookFields, "data" => $data])
                    ->build();

                $this->logManager->sendLog($event);

                return $this->render(
                    'FrontendBundle:Social:socialSignUp.html.twig',
                    [
                        'template'          => $template,
                        'data'              => $guestInfo,
                        'facebookFields'    => $facebookFields,
                        'form'              => $form->createView(),
                        'bounceValidator'   => (int) $this->bounceValidatorActive,
                        'requiredOptIn'     => $requiredOptIn,
                        'activeLegalBase'   => $activeLegalBase
                    ]
                );
            }

            if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO && !$consentTermAccepted){
                return $this->render(
                    'FrontendBundle:Social:socialSignUp.html.twig',
                    [
                        'template'               => $template,
                        'data'                   => $guestInfo,
                        'facebookFields'         => $facebookFields,
                        'form'                   => $form->createView(),
                        'bounceValidator'        => (int) $this->bounceValidatorActive,
                        'requiredOptIn'          => $requiredOptIn,
                        'consentTermNotAccepted' => !$consentTermAccepted,
                        'activeLegalBase'        => $activeLegalBase
                    ]
                );
            }

            if ($existUser != false) {
                if ($existUser->getStatus() == Guest::STATUS_INACTIVE) {

                    $analyticEvent = new Event();
                    $event = $analyticEvent->withClient($this->getLoggedClient())
                        ->withEventIdentifier(EventIdentifier::INACTIVE_GUEST)
                        ->withEventType(EventType::INACTIVE_GUEST)
                        ->withNas($nas)
                        ->withGuest($guest)
                        ->withRequest($request)
                        ->withSession($this->session)
                        ->withExtraData(null)
                        ->build();

                    $this->logManager->sendLog($event);

                    return $this->render(
                        'FrontendBundle:SignIn:signInInactive.html.twig',
                        [
                            'guest'    => $guest,
                            'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                        ]
                    );
                }

                if ($this->isAnExistentGuest($existUser, $guest)) {
                    $this->addUniqueGuestErrorToForm($existUser, $form);

                    return $this->render(
                        'FrontendBundle:Social:socialSignUp.html.twig',
                        [
                            'template'          => $template,
                            'data'              => $guestInfo,
                            'facebookFields'    => $facebookFields,
                            'form'              => $form->createView(),
                            'bounceValidator'   => (int) $this->bounceValidatorActive,
                            'requiredOptIn'     => $requiredOptIn,
                            'activeLegalBase'   => $activeLegalBase
                        ]
                    );
                }

                $this->accountingProcessor->process($client, $existUser);

                if ($socialType == Social::FACEBOOK) {
                    $this->guestSocialService->facebookFields($guest, $facebookFields);
                    return $this->facebookPublish($guest, $socialType);
                }

                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                        'guest'      => $existUser->getId(),
                        'socialType' => $socialType
                    ])
                );
            }

            if ($form->isValid()) {
                $emailValidate  = true;
                $emailField     = $this->customFieldsService->getFieldByNameType('email');

                if ($emailField) {
                    $emailValidate = $form->get('emailValidate')->getData();

                    if (!$emailValidate) {
                        $form->get('properties')['email']->addError(
                            new FormError($this->translator->trans('wspot.signup_page.field_valid_domain_email'))
                        );


                        $analyticEvent = new Event();
                        $event = $analyticEvent->withClient($this->getLoggedClient())
                            ->withEventIdentifier(EventIdentifier::COMPLETE_REGISTRATION)
                            ->withEventType(EventType::COMPLETE_REGISTRATION)
                            ->withNas($nas)
                            ->withGuest($guest)
                            ->withRequest($request)
                            ->withSession($this->session)
                            ->withExtraData(["facebook_fields" => $facebookFields, "data" => $data])
                            ->build();

                        $this->logManager->sendLog($event);

                        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
                        return $this->render(
                            'FrontendBundle:Social:socialSignUp.html.twig',
                            [
                                'template'          => $template,
                                'data'              => $guestInfo,
                                'facebookFields'    => $facebookFields,
                                'form'              => $form->createView(),
                                'bounceValidator'   => (int) $this->bounceValidatorActive,
                                'requiredOptIn'     => $requiredOptIn,
                                'activeLegalBase'   => $activeLegalBase
                            ]
                        );
                    }
                }

                try {
                    $guest = $this->guestService->createByFrontend(
                        $this->session->get(Nas::NAS_SESSION_KEY),
                        $guest,
                        $request->get('_locale'),
                        $emailValidate,
                        $socialType
                    );

                    $this->guestSocialService->create($guest, $guestData);
                    $this->session->set('newGuest', true);
                    $this->session->set('guestId', $guest->getId());

                    $this->accountingProcessor->process($client, $guest);

                    if ($this->twoFactorAuthAccessCode->isModuleActive()) {
                        $this->session->set('step', AccessCode::STEP_SOCIAL);
                        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                            'frontend_two_factor_auth_accesscode',
                            [
                                '_locale' => $request->get('_locale')
                            ]
                        ));
                    }

                    if (!$this->twoFactorAuthAccessCode->isModuleActive() ||
                        $this->session->get('twoFactorSignupVerified')
                    ) {
                        if ($socialType == Social::FACEBOOK) {
                            $this->guestSocialService->facebookFields($guest, $facebookFields);
                            return $this->facebookPublish($guest, $socialType);
                        }

                        return $this->controllerHelper->redirect(
                            $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                                'guest'      => $guest->getId(),
                                'socialType' => $socialType
                            ])
                        );
                    }
                } catch (UniqueFieldException $e) {
                    $field = $this->customFieldsService->getFieldByNameType($e->getMessage());
                    $form->get('properties')[$field->getIdentifier()]
                        ->addError(
                            new FormError(
                                $this->translator->trans('wspot.login_page.field_already_exists')
                            )
                        )
                    ;

                    $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
                    return $this->render(
                        'FrontendBundle:Social:socialSignUp.html.twig',
                        [
                            'template'          => $template,
                            'data'              => $guestInfo,
                            'facebookFields'    => $facebookFields,
                            'form'              => $form->createView(),
                            'bounceValidator'   => (int) $this->bounceValidatorActive,
                            'requiredOptIn'     => $requiredOptIn,
                            'activeLegalBase'   => $activeLegalBase
                        ]
                    );
                } catch (InvalidArgumentException $e) {
                    return $this->controllerHelper->redirect(
                        $this->controllerHelper->generateUrl(
                            'frontend_edit_data',
                            [
                                'fields'        => ['phone', 'register'],
                                'invalidNumber' => true,
                                'guest'         => $guest->getMysql()
                            ]
                        )
                    );
                } catch (InvalidSmsPhoneNumberException $e) {
                    return $this->controllerHelper->redirect(
                        $this->controllerHelper->generateUrl(
                            'frontend_edit_data',
                            [
                                'fields'        => ['phone', 'welcome'],
                                'invalidNumber' => true,
                                'guest'         => $guest->getMysql()
                            ]
                        )
                    );
                } catch (\Exception $e) {
                    $form = $this->getServerSideErrorResponse($form, $e->getMessage());

                    $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
                    return $this->render(
                        'FrontendBundle:Social:socialSignUp.html.twig',
                        [
                            'template'          => $template,
                            'data'              => $guestInfo,
                            'facebookFields'    => $facebookFields,
                            'form'              => $form->createView(),
                            'bounceValidator'   => (int) $this->bounceValidatorActive,
                            'requiredOptIn'     => $requiredOptIn,
                            'activeLegalBase'   => $activeLegalBase
                        ]
                    );
                }
            }
        }

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::COMPLETE_REGISTRATION)
            ->withEventType(EventType::COMPLETE_REGISTRATION)
            ->withNas($nas)
            ->withGuest($guest)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(["facebook_fields" => $facebookFields])
            ->build();

        $this->logManager->sendLog($event);

        
        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
        return $this->render(
            'FrontendBundle:Social:socialSignUp.html.twig',
            [
                'template'          => $template,
                'data'              => $guestInfo,
                'facebookFields'    => $facebookFields,
                'form'              => $form->createView(),
                'bounceValidator'   => (int) $this->bounceValidatorActive,
                'requiredOptIn'     => $requiredOptIn,
                'activeLegalBase'   => $activeLegalBase
                ]
        );
    }
    
    public function completeRegistrationOAuthAction(Request $request)
    {
        $oauthClientId = $request->query->get('oauth_client_id');
        if (!$this->session->get(Nas::NAS_SESSION_KEY)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');

        $this->session->remove('newGuest');

        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));
        $session    = $request->getSession();
        $guestData  = $session->get('guest');
        $guestInfo  = $guestData['data'];
        $guestOauthInfo  = $guestData['oauth_data'];


        if ($guestInfo === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'socialError' => $guestData['social']['type']
            ]));
        }

        $spotLoginField = $this->customFieldsService->getLoginFieldIdentifier();

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findLikeCustomField($spotLoginField, $guestInfo[$guestInfo['field_login']]);
        if ($guest != false && $guest->getStatus() == Guest::STATUS_INACTIVE) {
            return $this->render(
                'FrontendBundle:SignIn:signInInactive.html.twig',
                [
                    'guest'    => $guest,
                    'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                ]
            );
        }

        // Quando já estiver cadastrado
        if ($guest instanceof Guest) {
            $this->session->set('edit', $guest->getMysql());

            $this->accountingProcessor->process($client, $guest);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $guest->getId(),
                    'socialType' => Social::OAUTH
                ])
            );
        }

        $guest = new Guest();

        $guest->setLocale($request->get('_locale'));

        $guest->addProperty($spotLoginField, $guestInfo[$guestInfo['field_login']]);
        $fields = $this->customFieldsService->getCustomFields();
        foreach ($fields as $field) {
            $identifier = $field->getIdentifier();

            if ($identifier == $spotLoginField) {
                continue;
            }

            if (isset($guestOauthInfo[$identifier])) {
                $guest->addProperty($identifier, $guestOauthInfo[$identifier]);
            }
        }


        $formFields = [$guestInfo['field_login']];
        $form = $this->createCompleteRegistrationForm($guest, $formFields, 'complete_registration_oauth', ['oauth_client_id' => $oauthClientId]);

        $form->handleRequest($request);

        $requiredOptIn = (bool) $this->configurationService->get($nas, $this->getLoggedClient(), 'request_optin');


        $data       = $guest;
        $existUser  = $this->guestService->verifyUser($data);

        if ($existUser != false) {
            if ($existUser->getStatus() == Guest::STATUS_INACTIVE) {
                return $this->render(
                    'FrontendBundle:SignIn:signInInactive.html.twig',
                    [
                        'guest'    => $guest,
                        'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                    ]
                );
            }

            $this->guestSocialService->create($existUser, $guestData);

            $this->accountingProcessor->process($client, $existUser);


            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $existUser->getId(),
                    'socialType' => Social::OAUTH
                ])
            );
        }

        if($oauthClientId) {
            $oauthParams = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy(['clientId' => $oauthClientId, 'domain' => $client->getDomain()]);
        } else {
            $oauthParams = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy(['domain' => $client->getDomain()]);
        }
        $guestGroupID = $oauthParams->getCustomizeGuestGroup();

        if ($guestGroupID) {
            try {
                $guestGroupShortcode = $this->groupService->getGroupShortcodeById($guestGroupID);
                $guest->setGroup($guestGroupShortcode);
            } catch (\Exception $e) {
                $errorMessage = "Error adding a custom group to the guest." . $e->getMessage();
                $this->logger->addCritical($errorMessage);
                $guest->setGroup(Group::GROUP_EMPLOYEE);
            }
        }else{
            $guest->setGroup(Group::GROUP_EMPLOYEE);
        }

        $emailValidate  = true;
        $emailField     = $this->customFieldsService->getFieldByNameType('email');

        if ($emailField) {
            $emailValidate = $form->get('emailValidate')->getData();

            if (!$emailValidate) {
                $form->get('properties')['email']->addError(
                    new FormError($this->translator->trans('wspot.signup_page.field_valid_domain_email'))
                );
            }
        }

        if (!$emailValidate || ($request->isMethod('GET') && $oauthParams->getRequestMissingFields())) {
            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            return $this->render(
                'FrontendBundle:Social:socialSignUp.html.twig',
                [
                    'template'          => $template,
                    'data'              => $guestInfo,
                    'facebookFields'    => [],
                    'form'              => $form->createView(),
                    'bounceValidator'   => (int) $this->bounceValidatorActive,
                    'requiredOptIn'     => $requiredOptIn,
                    'activeLegalBase'   => $activeLegalBase
                ]
            );
        }

        try {
            $guest = $this->guestService->createByFrontend(
                $this->session->get(Nas::NAS_SESSION_KEY),
                $guest,
                $request->get('_locale'),
                $emailValidate,
                Social::OAUTH
            );

            $this->session->set('newGuest', true);
            $this->session->set('guestId', $guest->getId());

            $this->accountingProcessor->process($client, $guest);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $guest->getId(),
                    'socialType' => Social::OAUTH
                ])
            );
        } catch (UniqueFieldException $e) {
            $field = $this->customFieldsService->getFieldByNameType($e->getMessage());
            $form->get('properties')[$field->getIdentifier()]
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.login_page.field_already_exists')
                    )
                )
            ;

            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            return $this->render(
                'FrontendBundle:Social:socialSignUp.html.twig',
                [
                    'template'          => $template,
                    'data'              => $guestInfo,
                    'facebookFields'    => [],
                    'form'              => $form->createView(),
                    'bounceValidator'   => (int) $this->bounceValidatorActive,
                    'requiredOptIn'     => $requiredOptIn,
                    'activeLegalBase'   => $activeLegalBase
                ]
            );
        }

    }

    // VERIFICAR SE PRECISA PRO IXC

    public function completeRegistrationIntegrateAction(Request $request)
    {
        if (!$this->session->get(Nas::NAS_SESSION_KEY)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');

        $this->session->remove('newGuest');

        $template   = $this->templateService->templateSettings($this->session->get('campaignId'));
        $session    = $request->getSession();
        $guestData  = $session->get('guest');
        $guestInfo  = $guestData['data'];
        $guestFields = $guestData['fields'];
        $socialType = $guestData['social']['type'];
        $guestGroupID = $guestData['group_id'];

        if ($guestInfo === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'socialError' => $guestData['social']['type']
            ]));
        }

        $spotLoginField = $this->customFieldsService->getLoginFieldIdentifier();

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findLikeCustomField($spotLoginField, $guestInfo[$guestInfo['field_login']]);
        if ($guest != false && $guest->getStatus() == Guest::STATUS_INACTIVE) {
            return $this->render(
                'FrontendBundle:SignIn:signInInactive.html.twig',
                [
                    'guest'    => $guest,
                    'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                ]
            );
        }   

        // Quando já estiver cadastrado
        if ($guest instanceof Guest) {
            $this->session->set('edit', $guest->getMysql());

            foreach ($guestFields as $field => $value) {
                $guest->addProperty($field, $value);
            }

            if ($guestGroupID) {
                try {
                    $guestGroupShortcode = $this->groupService->getGroupShortcodeById($guestGroupID);
                    $guest->setGroup($guestGroupShortcode);
                } catch (\Exception $e) {
                    $errorMessage = "Error adding a custom group to the guest." . $e->getMessage();
                    $this->logger->addCritical($errorMessage);
                    $guest->setGroup(Group::GROUP_EMPLOYEE);
                }
            }else{
                $guest->setGroup(Group::GROUP_EMPLOYEE);
            }

            $this->guestService->persist($guest);

            $this->accountingProcessor->process($client, $guest);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $guest->getId(),
                    'socialType' => $socialType
                ])
            );
        }

        $guest = new Guest();

        $guest->setLocale($request->get('_locale'));

        $guest->addProperty($spotLoginField, $guestInfo[$guestInfo['field_login']]);
        foreach ($guestFields as $field => $value) {
            $guest->addProperty($field, $value);
        }
        $data = $guest;
        $existUser  = $this->guestService->verifyUser($data);

        if ($existUser != false) {
            if ($existUser->getStatus() == Guest::STATUS_INACTIVE) {
                return $this->render(
                    'FrontendBundle:SignIn:signInInactive.html.twig',
                    [
                        'guest'    => $guest,
                        'template' => $this->templateService->templateSettings($this->session->get('campaignId'))
                    ]
                );
            }

            $this->guestSocialService->create($existUser, $guestData);

            $this->accountingProcessor->process($client, $existUser);


            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $existUser->getId(),
                    'socialType' => $socialType
                ])
            );
        }

        if ($guestGroupID) {
            try {
                $guestGroupShortcode = $this->groupService->getGroupShortcodeById($guestGroupID);
                $guest->setGroup($guestGroupShortcode);
            } catch (\Exception $e) {
                $errorMessage = "Error adding a custom group to the guest." . $e->getMessage();
                $this->logger->addCritical($errorMessage);
                $guest->setGroup(Group::GROUP_EMPLOYEE);
            }
        }else{
            $guest->setGroup(Group::GROUP_EMPLOYEE);
        }

        foreach ($guestFields as $field => $value) {
            $guest->addProperty($field, $value);
        }
        // echo $socialType;
        $this->logger->info('Valor de socialType:', ['socialType' => $socialType]);

        try {
            $guest = $this->guestService->createByFrontend(
                $this->session->get(Nas::NAS_SESSION_KEY),
                $guest,
                $request->get('_locale'),
                true,
                $socialType
            );
            $this->logger->info("Usuário criado com sucesso com ID: " . $guest->getId());

            $this->session->set('newGuest', true);
            $this->session->set('guestId', $guest->getId());

            $this->accountingProcessor->process($client, $guest);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest'      => $guest->getId(),
                    'socialType' => $socialType
                ])
            );
        } catch (UniqueFieldException $e) {
            $this->logger->addCritical(
                'Duplicated guest on integration'
            );
        }

    }

    /**
     * @param Guest $guest
     * @param null $socialType
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function facebookPublish(Guest $guest, $socialType = null)
    {
        $nas     = $this->session->get(Nas::NAS_SESSION_KEY);
        $client  = $this->getLoggedClient();

        $hasShare = $this->configurationService->get($nas, $client, 'facebook_share');
        $hasLike    = $this->configurationService->get($nas, $client, 'facebook_like');

        if (!$guest) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'socialError' => 1
            ]));
        }
        $locale = !is_null($this->session->get('locale'))?$this->session->get('locale'):"pt_br";

        if ($hasShare || $hasLike) {
            /**
             * TODO -- (WSPOTNEW-3603)
             * TODO -- Aqui vamos remover o identificador do visitante caso esteja na sessão.
             */
            $facebookHelper             = new FacebookRedirectLikeAndShareHelper($this->session);
            $hasFacebookShareOnSession  = $facebookHelper->getFromSession($guest->getMysql());
            if ($hasFacebookShareOnSession) {
                $facebookHelper->removeFromSession($guest->getMysql());
            }

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($this->getLoggedClient())
                ->withEventIdentifier(EventIdentifier::PUBLISH_ACTION)
                ->withEventType(EventType::PUBLISH_ACTION)
                ->withNas($nas)
                ->withGuest($guest)
                ->withRequest(null)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    'publish_actions_facebook',
                    [
                        'guest'      => $guest->getId(),
                        'socialType' => $socialType,
                        'language' => $locale,
                        '_locale' => $locale
                    ]
                )
            );
        }

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                'guest'      => $guest->getId(),
                'socialType' => $socialType,
                'language' => $locale,
                '_locale' => $locale
            ])
        );
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws GuestNotFoundException
     * @throws \ReflectionException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
     */
    public function completeRegistrationConfirmation(Request $request)
    {
        $guestId    = $request->get('guest');
        $socialType = $request->get('socialType');
        $nas        = $this->session->get(Nas::NAS_SESSION_KEY);

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneById($guestId);

        if (!$guest) {
            $this->logger->addCritical(
                'Guest null on completeRegistrationConfirmation() - ' . substr(serialize($guest), 0, 250)
            );

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'socialError' => 1
            ]));
        }

        if (!$nas) {
            $this->logger->addWarning("Nas created manually on completeRegistrationConfirmation. Guest: {$guest->getId()}");
            $nas = $this->nasService->createNasManually($guest->getNasVendor(), $guest);
            $this->session->set(Nas::NAS_SESSION_KEY, $nas);
        }

        if ($socialType) {
            $this->logMethod($socialType, $guest);
        }

        $confirmation = $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl('frontend_guest_confirmation', [
                'guest' => $guest->getId()
            ])
        );

        if ($confirmation) {
            return $confirmation;
        }

        $newGuest = $this->session->get('newGuest');

        if ($newGuest) {
            $this->guestService->confirm($guest, $nas);
        }

        $this->session->remove('newGuest');

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($this->getLoggedClient());
        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
            try {
                $traceHeaders = TracerHeaders::from($request);
                $this->processSignUpConfirmationConsentSignature($guest, $this->getLoggedClient(), $nas, $traceHeaders);
            } catch (ConsentErrorException $e) {
                $this->logger->addCritical($e->getMessage());
            }
        }

        return $this->nasService->process($guest, $nas);
    }

    /**
     * @param $entity
     * @param $fields
     * @param $actionForm
     * @return FormInterface
     */
    private function createCompleteRegistrationForm($entity, $fields, $actionForm, $parameters = [])
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->getLoggedClient();

        return $this->controllerHelper->createForm(
            SocialMediaRegistrationType::class,
            $entity,
            [
                'action'            => $this->controllerHelper->generateUrl($actionForm, $parameters),
                'fields'            => $fields,
                'method'            => 'POST',
                'authorize_email'   => $this->configurationService->get($nas, $client, 'authorize_email')
            ]
        );
    }

    /**
     * @param $social
     * @param $guest
     */
    protected function logMethod($social, $guest)
    {
        switch ($social) {
            case Social::FACEBOOK:
                $this->eventDispatcher->dispatch(
                    'core.event.authentication',
                    new AuthenticationEvent($guest, 'facebook')
                );
                break;
            case Social::TWITTER:
                $this->eventDispatcher->dispatch(
                    'core.event.authentication',
                    new AuthenticationEvent($guest, 'twitter')
                );
                break;
            case Social::LINKEDIN:
                $this->eventDispatcher->dispatch(
                    'core.event.authentication',
                    new AuthenticationEvent($guest, 'linkedin')
                );
                break;
            default:
                $this->eventDispatcher->dispatch(
                    'core.event.authentication',
                    new AuthenticationEvent($guest, 'social')
                );
                break;
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function handleFormFields(array $data)
    {
        $fields = [];

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $fields[] = $key;
            }
        }
        return $fields;
    }

    /**
     * @param FormInterface $signUpForm
     * @param Template $template
     * @param $field
     * @return \Wideti\WebFrameworkBundle\Service\Router\Response
     */
    private function getUniqueFieldErrorResponse(FormInterface $signUpForm, Template $template, $field)
    {
        $signUpForm->get('properties')[$field->getIdentifier()]
            ->addError(
                new FormError(
                    $this->translator->trans('wspot.login_page.field_already_exists')
                )
            );

        return $this->routerService->forward(
            'wspot.frontend.controller.frontend:indexAction',
            [
                'template'   => $template,
                'signUpForm' => $signUpForm->createView(),
                'autoLogin'  => 0
            ]
        );
    }

    /**
     * @param $guest
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function getInvalidPhoneSmsErrorResponse(Guest $guest, $type = 'welcome')
    {
        $fieldIdentifier = isset($guest->getProperties()['mobile']) ? 'mobile' : 'phone';

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl(
                'frontend_edit_data',
                [
                    'fields'        => [$fieldIdentifier, $type],
                    'invalidNumber' => true,
                    'guest'         => $guest->getMysql()
                ]
            )
        );
    }

    /**
     * @param FormInterface $form
     * @param $message
     * @return FormInterface
     */
    private function getServerSideErrorResponse(FormInterface $form, $message)
    {
        if ($message == 'invalid_email') {
            $form->get('properties')['email']
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.signup_page.field_valid_email')
                    )
                );
        } elseif ($message == 'invalid_domain') {
            $form->get('properties')['email']
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.signup_page.field_valid_domain_email')
                    )
                );
        } elseif ($message == 'invalid_document') {
            $form->get('properties')['document']
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.signup_page.field_invalid_document')
                    )
                );
        } elseif ($message == 'invalid_phone') {
            $field = array_key_exists('phone', $form->get('properties')->getData()) ? 'phone' : 'mobile';
            $form->get('properties')[$field]
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.signup_page.field_phone_min_characters_required')
                    )
                );
        } elseif ($message == 'invalid_birthdate') {
            $form->get('properties')['data_nascimento']
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.signup_page.field_invalid_date')
                    )
                );
        }elseif ($message == 'age_restriction') {
           $form->get('properties')['age_restriction']
               ->addError(
                   new FormError(
                       $this->translator->trans('wspot.signup_page.field_age_restriciton_error')
                   )
               );
        } else {
            $this->logger->addCritical("Fail to create guests by form. ${message}");
            $form
                ->addError(
                    new FormError(
                        $this->translator->trans('wspot.signup_page.general_error')
                    )
                );
        }

        return $form;
    }

    /**
     * @param Guest $guest
     * @param Nas|null $nas
     * @throws \Exception
     */
    private function sendConfirmationSMS(Guest $guest, Nas $nas = null)
    {
        $params = [
            'domain'    => $this->getLoggedClient()->getDomain(),
            'guestId'   => $guest->getId(),
            'locale'    => $guest->getLocale(),
            'action'    => NotificationType::CONFIRMATION
        ];

        $notificationService = $this->controllerHelper->getContainer()->get(NotificationType::CONFIRMATION);
        $notificationService->sendSMS($nas, $params);
    }

    /**
     * @param Guest $guest
     * @return mixed $accessData
     * @internal param $accessData
     */
    private function formatLowerCase(Guest $guest)
    {
        if (array_key_exists('email', $guest->getProperties())) {
            $properties          = $guest->getProperties();
            $properties['email'] = strtolower($properties['email']);
            $guest->setProperties($properties);
        }
        return $guest;
    }

    /**
     * @param Nas|null $nas
     */
    private function verifyPocLimitSMS(Nas $nas = null)
    {
        /**
         * @var Client $client
         */
        $client = $this->session->get('wspotClient');

        if ($this->smsService->checkLimitSendSms(true)
            && $this->configurationService->get($nas, $client, 'confirmation_sms') == 1
        ) {
            $this->configurationService->updateKey("confirmation_sms", 0, $client);
            $this->configurationService->updateKey("enable_confirmation", 0, $client);
        }
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
     * @param $str
     * @param $startingWord
     * @param $endingWord
     * @return false|string
     */
    private function stringBetweenTwoString($str, $startingWord, $endingWord)
    {
        $subtringStart = strpos($str, $startingWord);
        //Adding the strating index of the strating word to
        //its length would give its ending index
        $subtringStart += strlen($startingWord);
        //Length of our required sub string
        $size = strpos($str, $endingWord, $subtringStart) - $subtringStart;
        // Return the substring from the index substring_start of length size
        return substr($str, $subtringStart, $size);
    }

    /**
     * @param Guest $existUser
     * @param Guest $guest
     * @return bool
     */
    private function isAnExistentGuest(Guest $existUser, Guest $guest)
    {
        return $existUser->getPropertyByKey($existUser->getLoginField()) ==
            $guest->getPropertyByKey($existUser->getLoginField());
    }

    /**
     * @param Guest $existUser
     * @param FormInterface $form
     */
    private function addUniqueGuestErrorToForm(Guest $existUser, FormInterface $form)
    {
        $field = $this->customFieldsService->getFieldByNameType($existUser->getLoginField());
        $form->get('properties')[$field->getIdentifier()]
            ->addError(
                new FormError(
                    $this->translator->trans('wspot.login_page.field_already_exists')
                )
            );
    }


    /**
     * @param Guest $guest
     * @param Client $client
     * @return Signature
     */
    private function executeConsentProcess(Guest $guest, Client $client)
    {
        return $this->consent->findSignature($guest, $client);
    }

    public function signConsentAction(Request $request, $isSignProcess = false, $status="INACTIVE")
    {
        $client = $this->getLoggedClient();
        $guest = $request->query->get('guest');

        $signValue = $request->query->get('isSignProcess');
        if (!is_null($signValue)) {
            $isSignProcess = $signValue;
        }

        $identifier = '';
        $wspotNas = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($wspotNas) {
            $identifier = $wspotNas->getAccessPointMacAddress();
        }

        $template = $this->templateService->templateSettings($this->session->get('campaignId'));
        $config = $this->configurationService->getByIdentifierOrDefault($identifier, $this->getLoggedClient());

        if (!isset($guest)) {

            $this->logger
                ->addCritical("Guest not found request to sign consent on client {$client->getId()}");
        }

        try {
            $dto = new OneGuestQueryDto();
            $dto->setMysql($guest);
            $guest = $this->guestService->getOneGuest($dto);
            $traceHeaders = TracerHeaders::from($request);
            $cons = $this->getConsentGateway->get($client, "pt_BR", $traceHeaders);

            if ($isSignProcess) {
                $guestDto = new OneGuestQueryDto();
                $guestDto->setMysql($guest->getMysql());
                $response = $this->consent->signConsent($guest, $cons, $traceHeaders);

                if (isset($response) && !is_null($response)) {
                    $guest = $this->guestService->getOneGuest($guestDto);
                    $nas = $this->session->get(Nas::NAS_SESSION_KEY);
                    $this->guestService->grantSignedConsent($guest);
                    return $this->nasService->process($guest, $nas);
                }
                $this->logger
                    ->addCritical("Fail to sign consent for guest: {$guest->getMysql()} on client: {$client->getId()}");
            }


            return $this->render("@Frontend/SignIn/signConsent.twig", [
                'config' => $config,
                'template' => $template,
                'templateCSS' => $template->getBackgroundCSSConfiguration(),
                'formUrl' => "https://forms.gle/UZtbHLb2JNrnCcX27",
                'consent' => $cons,
                'guest' => $guest->getMysql()
            ]);
        } catch (ConsentErrorException $e) {
            $this->logger
                ->addCritical("Consent error server fail to response for guest {$guest->getMysql()} on client {$client->getId()} with message {$e->getMessage()}");
            return $this->render(
                'FrontendBundle:SignIn:consentProblem.html.twig',
                [
                    'template' => $template
                ]
            );
        } catch (\Exception $e) {
            $this->logger
                ->addCritical("Consent server fail to response for guest {$guest->getMysql()} on client {$client->getId()} with message {$e->getMessage()}");
            return $this->render(
                'FrontendBundle:SignIn:consentProblem.html.twig',
                [
                    'template' => $template
                ]
            );
        }
    }


    private function guestRollback(Guest $guest) {
        $this->guestService->deleteGuestInAllBases($guest);
    }

    private function signConsent(Client $client, Guest $guest, $headers = []) {
        $consents = $this->getConsentGateway->get($client, 'pt_BR', $headers);
        $signedConsent = $this->consent->signConsent($guest, $consents, $headers);
        if (is_null($signedConsent->getError())) {
            $this->guestService->grantSignedConsent($guest);
        }
        return $signedConsent;
    }

    private function processSignUpConfirmationConsentSignature(Guest $guest, Client $client, $nas, $headers = []) {

        try {
            $signedConsent = $this->executeConsentProcess($guest, $client);

            if ($this->isSignatureRequestError($signedConsent)) {
                $this->logger->addCritical($signedConsent->getError()->getMessage());
                throw new ConsentErrorException($signedConsent->getError()->getMessage());
            }

            if ($this->isSignatureNotFound($signedConsent)){
                return $this->signConsent($client, $guest, $headers);
            }

        } catch (\Exception $e) {
            throw new ConsentErrorException($e);
        }
    }

    private function processSignUpConsentSignature(Guest $guest, Client $client, $nas, $isOn, $headers = []) {
        try {
            $signedConsent = $this->executeConsentProcess($guest, $client);

            if ($this->isSignatureRequestError($signedConsent)) {
                $this->logger->addCritical($signedConsent->getError()->getMessage());
                throw new ConsentErrorException($signedConsent->getError()->getMessage());
            }

            if ($this->isSignatureNotFound($signedConsent) && $isOn == "on"){
                $result = $this->signConsent($client, $guest, $headers);
                return true;
            }


        } catch (\Exception $e) {
            throw new ConsentErrorException($e->getMessage());
        }
        return false;
    }

    private function processSignInConsentSignature(Guest $guest, Client $client){

        try {
            $signedConsent = $this->executeConsentProcess($guest, $client);

            if ( !is_null($signedConsent->getStatus()) && $signedConsent->getStatus() == "ACTIVE" ) {
                return true;
            }

            if ($this->isSignatureNotFound($signedConsent)){
                return false;
            }

            if ($this->isSignatureRequestError($signedConsent)) {
                $this->logger->addCritical($signedConsent->getError()->getMessage());
                throw new ConsentErrorException($signedConsent->getError()->getMessage());
            }

        } catch (\Exception $e) {
            throw new ConsentErrorException($e->getMessage());
        }
        return false;
    }

    private function isSignatureNotFound(Signature $signature) {
        return !is_null($signature->getError()) && $signature->getError()->getCode() == 404;
    }

    private function isSignatureRequestError(Signature $signature) {
        return !is_null($signature->getError()) && $signature->getError()->getCode() != 404;
    }

}
