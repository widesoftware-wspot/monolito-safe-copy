<?php

namespace Wideti\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeControl;
use Wideti\DomainBundle\Entity\TwoFactorAuth;
use Wideti\DomainBundle\Exception\CustomFieldNotFoundException;
use Wideti\DomainBundle\Helpers\SpecialCharactersHelper;
use Wideti\DomainBundle\Helpers\CheckHasValidateCharacterSpecial;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeServiceImp;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\Template\TemplateService;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Service\TwoFactorAuth\TwoFactorAuthService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Form\AccessCodeType;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class TwoFactorAuthController implements NasControllerHandler
{
    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use SessionAware;
    use TranslatorAware;
    use LoggerAware;
    use NasServiceAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var TwoFactorAuthService
     */
    private $twoFactorAuthHapVida;
    /**
     * @var TwoFactorAuthService
     */
    private $twoFactorAuthAccessCode;
    /**
     * @var TemplateService
     */
    private $templateService;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var AccessCodeServiceImp
     */
    private $accessCodeService;

    /**
     * @var EventLoggerManager
     */
    private $logManager;

    /**
     * @var GuestService
     */
    private $guestService;

    /**
     * TwoFactorAuthController constructor.
     * @param FrontendControllerHelper $controllerHelper
     * @param TwoFactorAuthService $twoFactorAuthHapVida
     * @param TwoFactorAuthService $twoFactorAuthAccessCode
     * @param TemplateService $templateService
     * @param CustomFieldsService $customFieldsService
     * @param CacheServiceImp $cacheService
     * @param AccessCodeServiceImp $accessCodeService
     * @param EventLoggerManager $logManager
     * @param GuestService $guestService
     */
    public function __construct(
        FrontendControllerHelper $controllerHelper,
        TwoFactorAuthService $twoFactorAuthHapVida,
        TwoFactorAuthService $twoFactorAuthAccessCode,
        TemplateService $templateService,
        CustomFieldsService $customFieldsService,
        CacheServiceImp $cacheService,
        AccessCodeServiceImp $accessCodeService,
        EventLoggerManager $logManager,
        GuestService $guestService
    ) {
        $this->twoFactorAuthHapVida     = $twoFactorAuthHapVida;
        $this->twoFactorAuthAccessCode  = $twoFactorAuthAccessCode;
        $this->templateService          = $templateService;
        $this->customFieldsService      = $customFieldsService;
        $this->controllerHelper         = $controllerHelper;
        $this->cacheService             = $cacheService;
        $this->accessCodeService        = $accessCodeService;
        $this->logManager               = $logManager;
        $this->guestService             = $guestService;
    }

    public function accessCodeAction(Request $request)
    {
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        if (!$nas) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_preview'));
        }

        $routeToRedirect = 'frontend_index';
        $locale          = $request->get('_locale');
        $step            = $this->session->get('step');
        $parameterStep   = $request->get('step_confirmation');
        $guestMysql      = $this->session->get('gm');
        $this->session->set('accessCodeOwner', 0);

        if (is_null($step)) {
            $step = $request->get("step");
        }

        if ($step == AccessCode::STEP_LOGIN) {
            $this->session->set('pre-login', true);
        } else {
            $this->session->set('pre-login', false);
        }


        if ($step == AccessCode::STEP_SIGNUP) {
            $routeToRedirect = 'frontend_signup_action';
        }

        if ($step == AccessCode::STEP_SOCIAL) {
            $routeToRedirect = 'complete_registration';
            $step = AccessCode::STEP_SIGNUP;
        }

        if ($step == AccessCode::STEP_SIGNUP_CONFIRMATION) {
            $step = AccessCode::STEP_SIGNUP;
        }


        $availableAccessCode = $this->accessCodeService->getAvailableAccessCodes($nas, $step);

        if (!$availableAccessCode->isHasAccessCode()) {
            $this->setTwoFactorVerified($step);
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    $routeToRedirect,
                    [
                        '_locale' => $locale
                    ]
                )
            );
        }

        $enableFreeAccess = $availableAccessCode->isHasFreeAccess();
        $freeAccessTime   = $availableAccessCode->getFreeAccessParams();

        $form = $this->controllerHelper->createForm(
            AccessCodeType::class,
            null,
            [
                'action' => $this->controllerHelper->generateUrl('frontend_two_factor_auth_accesscode', ['step'=>$step, 'step_confirmation' => $parameterStep]),
                'method' => 'POST'
            ]
        );

        $form->handleRequest($request);
        if ($form->isValid()) {
            if (!$availableAccessCode->isHasAccessCode()) {

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($this->getLoggedClient())
                    ->withEventIdentifier(EventIdentifier::SIGN_UP_ACCESS_CODE)
                    ->withEventType(EventType::SIGN_UP_ACCESS_CODE)
                    ->withNas($nas)
                    ->withRequest($request)
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);

                return $this->render(
                    'FrontendBundle:TwoFactorAuth:accessCode.html.twig',
                    [
                        'template'          => $this->templateService->templateSettings(
                            $this->session->get('campaignId')
                        ),
                        'form'              => $form->createView(),
                        'invalidMessage'    => $this->translator->trans('wspot.access_code.invalid_code'),
                        'enableFreeAccess'  => $enableFreeAccess,
                        'freeAccessTime'    => ($freeAccessTime) ? $freeAccessTime['freeAccessTime'] : '',
                        'step'              => $step
                    ]
                );
            }

            $data       = $form->getData();
            $hasCharacterSpecial = SpecialCharactersHelper::checkIfHasSpecialCharacters($data['code']);
            $accessCode = false;

            if (!$hasCharacterSpecial) {
                $accessCode = $this->accessCodeService->findAccessCode($availableAccessCode, strtoupper($data['code']));
            }

            if (!$accessCode) {

                $analyticEvent = new Event();
                $event = $analyticEvent->withClient($this->getLoggedClient())
                    ->withEventIdentifier(EventIdentifier::SIGN_UP_ACCESS_CODE)
                    ->withEventType(EventType::SIGN_UP_ACCESS_CODE)
                    ->withNas($nas)
                    ->withRequest($request)
                    ->withSession($this->session)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);

                return $this->render(
                    'FrontendBundle:TwoFactorAuth:accessCode.html.twig',
                    [
                        'template'          => $this->templateService->templateSettings(
                            $this->session->get('campaignId')
                        ),
                        'form'              => $form->createView(),
                        'invalidMessage'    => $this->translator->trans('wspot.access_code.invalid_code'),
                        'enableFreeAccess'  => $enableFreeAccess,
                        'freeAccessTime'    => ($freeAccessTime) ? $freeAccessTime['freeAccessTime'] : '',
                        'step'              => $step
                    ]
                );
            }
            if ($accessCode->getAccessCodeParams()['type'] == AccessCode::TYPE_RANDOM) {
                $validate = $this->accessCodeService->validateCode($accessCode, $nas);

                if ($validate !== null) {
                    $analyticEvent = new Event();
                    $event = $analyticEvent->withClient($this->getLoggedClient())
                        ->withEventIdentifier(EventIdentifier::SIGN_UP_ACCESS_CODE)
                        ->withEventType(EventType::SIGN_UP_ACCESS_CODE)
                        ->withNas($nas)
                        ->withRequest($request)
                        ->withSession($this->session)
                        ->withExtraData(null)
                        ->build();

                    $this->logManager->sendLog($event);

                    if (!is_null($parameterStep)) {
                        $step = AccessCode::STEP_SIGNUP;
                    }

                    return $this->render(
                        'FrontendBundle:TwoFactorAuth:accessCode.html.twig',
                        [
                            'template'          => $this->templateService->templateSettings(
                                $this->session->get('campaignId')
                            ),
                            'form'              => $form->createView(),
                            'invalidMessage'    => $validate,
                            'enableFreeAccess'  => $enableFreeAccess,
                            'freeAccessTime'    => ($freeAccessTime) ? $freeAccessTime['freeAccessTime'] : '',
                            'step'              => $step
                        ]
                    );
                }
            }

            $guestId = isset($accessCode->getAccessCodeParams()['username'])
                ? $accessCode->getAccessCodeParams()['username']->getId()
                : null;
            if ($guestId) {
                $this->session->set('accessCodeOwner', $guestId);
            }

            $this->session->set('accessCodeDto', $accessCode);
            $this->setTwoFactorVerified($step);
            $this->session->remove('freeAccessTime');

            if ($step == AccessCode::STEP_SIGNUP) {
                $guest = $this->guestService->getGuestById($this->session->get("guestId"));
                if($guest) {
                    $this->setGuestStatusVerified($guest->getMysql(), $step);
                    $this->session->set('accessCodeOwner', $guest->getMysql());
                }
            }

            if ($parameterStep == AccessCode::STEP_SIGNUP_CONFIRMATION ) {
                $this->setGuestStatusVerified($guestMysql, $step);
                $guest = $this->guestService->getGuestByMysql($guestMysql);
                $this->session->set('accessCodeOwner', $guest->getMysql());
                return $this->nasService->process($guest, $nas);
            }

            if ($parameterStep == AccessCode::STEP_SIGNIN){
                $this->setGuestStatusVerified($guestMysql, $step);
                $guest = $this->guestService->getGuestByMysql($guestMysql);
                $nas = $this->nasService->process($guest, $nas);
                if ($nas !== null) {
                    return $nas;
                }
                $this->session->set('isValidated', true);

                $autoLogin = $this->session->get('autoLogin');
                $this->session->remove('autoLogin');
                if ($autoLogin) {
                    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                        "frontend_start_navigation",
                        [
                            '_locale' => $locale,
                        ]
                    ));
                }
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl(
                    "frontend_signin_action",
                    [
                        '_locale' => $locale,
                    ]
                ));
            }

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    $routeToRedirect,
                    [
                        '_locale' => $locale
                    ]
                )
            );
        }


        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::SIGN_UP_ACCESS_CODE)
            ->withEventType(EventType::SIGN_UP_ACCESS_CODE)
            ->withNas($nas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:TwoFactorAuth:accessCode.html.twig',
            [
                'template'          => $this->templateService->templateSettings($this->session->get('campaignId')),
                'form'              => $form->createView(),
                'invalidMessage'    => null,
                'enableFreeAccess'  => $enableFreeAccess,
                'freeAccessTime'    => ($freeAccessTime) ? $freeAccessTime['freeAccessTime'] : '',
                'step'              => $step
            ]
        );
    }

    public function accessCodeFreeAccess(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $routeToRedirect = 'frontend_index';
            $step            = $request->get('step');

            if ($step == AccessCode::STEP_SIGNUP) {
                $routeToRedirect = 'frontend_signup_action';
            }

            if ($step == AccessCode::STEP_SOCIAL) {
                $routeToRedirect = 'complete_registration';
                $step = AccessCode::STEP_SIGNUP;
            }

            $this->session->set('twoFactorLoginVerified', true);
            $this->session->set('twoFactorSignupVerified', true);
            $this->session->set('freeAccessTime', true);

            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl(
                    $routeToRedirect,
                    [
                        '_locale' => $request->get('_locale')
                    ]
                )
            );
        }

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
    }

    public function hapVidaAction(Request $request)
    {
	    /**
	     * @var TwoFactorAuth $twoFactorAuth
	     */
        $twoFactorAuth = $this->twoFactorAuthHapVida->getTwoFactorAuthObject('hapvida');

        if ($twoFactorAuth) {
            $field = $this->customFieldsService->getFieldByNameType($twoFactorAuth->getField());

            if (!$field) {
                throw new CustomFieldNotFoundException('Custom Field not found on TwoFactorAuthController');
            }

            $analyticEvent = new Event();
            $event = $analyticEvent->withClient($this->getLoggedClient())
                ->withEventIdentifier(EventIdentifier::HAPVIDA_TWO_FACTOR)
                ->withEventType(EventType::HAPVIDA_TWO_FACTOR)
                ->withNas(null)
                ->withRequest($request)
                ->withSession($this->session)
                ->withExtraData(null)
                ->build();

            $this->logManager->sendLog($event);

            return $this->render(
                'FrontendBundle:TwoFactorAuth:hapVida.html.twig',
                [
                    'template'  => $this->templateService->templateSettings($this->session->get('campaignId')),
                    'message'   => $twoFactorAuth->getMessage()[$request->get('_locale')],
                    'field'     => $field,
                    'locale'    => $request->get('_locale')
                ]
            );
        }
    }

    public function verifyFactorAction(Request $request)
    {
        $value = $request->get('value');

        $respAuth = $this->twoFactorAuthHapVida->isAuthorized($value);

        if ($respAuth->isAuthorized()) {
            $this->session->set('twoFactorVerified', true);

            return new JsonResponse([
                'isAuthorized' => $respAuth->isAuthorized(),
                'message' => $respAuth->getMessage()
            ], 200);
        }

        return new JsonResponse([
            'isAuthorized' => $respAuth->isAuthorized(),
            'message' => $respAuth->getMessage()
        ], 401);
    }

    private function setTwoFactorVerified($step)
    {
        if ($step == AccessCode::STEP_LOGIN) {
            $this->session->set('twoFactorLoginVerified', true);
            $this->session->set('twoFactorSignupVerified', false);
        } else {
            $this->session->set('twoFactorSignupVerified', true);
        }
    }

    /**
     * @param $guestID
     * @param $step
     * @return void
     */
    private function setGuestStatusVerified($guestID, $step) {
        $controlRepo = $this->em->getRepository('DomainBundle:AccessCodeControl');
        $control = $controlRepo->findOneBy(["guestId" => $guestID]);

        if ($control) {
            $control->setAlreadyUsedAccessCode(true);
            $control->setHasToUseAccessCode(false);
            $this->em->persist($control);
            $this->em->flush();
        } else {
            $client = $this->session->get('wspotClient');
            $control = new AccessCodeControl($client->getId(), $guestID, false, true);
            $this->em->persist($control);
            $this->em->flush();
        }

    }

}
