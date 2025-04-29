<?php

namespace Wideti\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\CustomFields\Fields\Date;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\InvalidSmsPhoneNumberException;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\ControllerHelper;
use Wideti\DomainBundle\Helpers\FieldsHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Base\NotificationType;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsService;
use Wideti\DomainBundle\Service\GuestPasswordRecovery\PasswordRecoveryManagerInterface;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\SecretQuestion\Data\AnswerValidate;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\Fail;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\Forbidden;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\Locked;
use Wideti\DomainBundle\Service\SecretQuestion\SecretQuestionManagerInterface;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\FrontendBundle\Form\ForgotPasswordType;
use Wideti\FrontendBundle\Form\ChangeUserDataType;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;

class GuestProfileController implements NasControllerHandler
{
    use SessionAware;
    use RouterServiceAware;
    use TemplateAware;
    use MongoAware;
    use TwigAware;
    use GuestServiceAware;
    use NasServiceAware;
    use TranslatorAware;
    use EntityManagerAware;
    use CustomFieldsAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var EventLoggerManager
     */
    private $logManager;
    /**
     * @var Auditor
     */
    private $auditor;
    /**
     * @var SecretQuestionManagerInterface
     */
    private $secretQuestionManager;
    /**
     * @var PasswordRecoveryManagerInterface
     */
    private $passwordRecoveryManager;

    /**
     * GuestProfileController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param EventLoggerManager $logManager
     * @param Auditor $auditor
     */
    public function __construct(
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        CacheServiceImp $cacheService,
        EventLoggerManager $logManager,
        Auditor $auditor,
        SecretQuestionManagerInterface $secretQuestionManager,
        PasswordRecoveryManagerInterface $passwordRecoveryManager
    ) {
        $this->configurationService       = $configurationService;
        $this->controllerHelper           = $controllerHelper;
        $this->cacheService               = $cacheService;
        $this->logManager                 = $logManager;
        $this->auditor                    = $auditor;
        $this->secretQuestionManager      = $secretQuestionManager;
        $this->passwordRecoveryManager    = $passwordRecoveryManager;
    }

    public function forgetPasswordAction(Request $request)
    {
        $nas       = $this->session->get(Nas::NAS_SESSION_KEY);
        $client    = $this->getLoggedClient();
        $template  = $this->templateService->templateSettings($this->session->get('campaignId'));
        if (!$nas) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        $error = '';
        $hasDocument    = $this->mongo->getRepository('DomainBundle:CustomFields\Field')->hasField('document');
        $hasEmail       = $this->customFieldsService->getFieldByNameType('email');
        $loginField     = $this->customFieldsService->getLoginFieldIdentifier();
        $forget_password_choice = $request->get('forget_password_choice');
        $step           = $request->get('step', null);
        $form = $this->controllerHelper->createForm(
            ForgotPasswordType::class,
            null,
            [
                'action'    => $this->controllerHelper->generateUrl('frontend_forget_password'),
                'method'    => 'POST',
                'attr'      => [ 'step' => 'one']
            ]
        );
        
        $form->handleRequest($request);
        if ($request->getMethod() == "POST" && $request->get('_answer') != "" && !$forget_password_choice && !$step){
            $mySqlGuestId = $request->get("_guest_id");
            $answer = $request->get('_answer');
            /** @var Guest $guest */
            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'mysql' => intval($mySqlGuestId)
                ]);
            $guest->setLocale($request->getLocale());

            try {
                $validate = AnswerValidate::create($mySqlGuestId, $answer);
                $this->secretQuestionManager->validate($validate);

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
            }catch (Forbidden $forbidden){
                $questionAnswered = $this->secretQuestionManager->getQuestionAnsweredInfo($mySqlGuestId);
                $msgErr = $this->translator->trans('wspot.secret_answer_recovery.retry_msg');
                $msgErr = str_replace('%tentativas%', "0". $forbidden->getAttemptsAvailable(), $msgErr);
                return $this->render(
                    'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                    [
                        'wspotNas'         => $nas,
                        'template'         => $template,
                        'form'             => $form->createView(),
                        'error'            => $error,
                        'hasDocument'      => $hasDocument,
                        'hasEmail'         => $hasEmail,
                        'questionAnswered' => $questionAnswered,
                        'step'             => 'step-validate-answer',
                        'msgErr'           => $msgErr
                    ]
                );
            }catch (Locked $locked){
                $this->passwordRecoveryManager->lockRecovery($guest);

                return $this->render(
                    'FrontendBundle:ForgetPassword:recoveryPasswordLocked.html.twig',
                    [
                        'wspotNas'      => $nas,
                        'template'      => $template
                    ]
                );

            }catch (Fail $fail){
                $questionAnswered = $this->secretQuestionManager->getQuestionAnsweredInfo($mySqlGuestId);
                $msgErr = 'Ocorreu um erro no servidor. Tente novamente mais tarde.';
                return $this->render(
                    'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                    [
                        'wspotNas'         => $nas,
                        'template'         => $template,
                        'form'             => $form->createView(),
                        'error'            => $error,
                        'hasDocument'      => $hasDocument,
                        'hasEmail'         => $hasEmail,
                        'questionAnswered' => $questionAnswered,
                        'step'             => 'step-validate-answer',
                        'msgErr'           => $msgErr
                    ]
                );
            }
        } else if ($request->getMethod() == "POST" && !$step && $forget_password_choice == '') {
            $data       = $form->getData();
            $guestData  = $data->getProperties();

            $usernameSearch = ($loginField == "email") ? new \MongoRegex('/.*'.$guestData[$loginField].'.*/i') : $guestData[$loginField];
            $mySqlGuestId = $request->get("_guest_id");
            $answer = $request->get('_answer');
            /** @var Guest $guest */
            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'properties.' . $loginField => $usernameSearch
                ]);
            $guest->setLocale($request->getLocale());
            $render_secret_answer_recovery = $guest->isHasSecurityAnswer() && $client->hasGuestPasswordRecoverySecurity();
            $render_email_recovery         = $client->getGuestPasswordRecoveryEmail();
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
        if ($form->isValid() || $forget_password_choice == 'email' || $forget_password_choice == 'secret-answer' || $step == 'one') {
            $data       = $form->getData();
            $guestData  = $data->getProperties();

            $usernameSearch = ($loginField == "email") ? new \MongoRegex('/.*'.$guestData[$loginField].'.*/i') : $guestData[$loginField];
            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'properties.' . $loginField => $usernameSearch
                ]);
            if (!$forget_password_choice) {
                if (!is_null($guest) && $this->passwordRecoveryManager->recoveryIsLocked($guest)){
                    return $this->render(
                        'FrontendBundle:ForgetPassword:recoveryPasswordLocked.html.twig',
                        [
                            'wspotNas'      => $nas,
                            'template'      => $template
                        ]
                    );
                }

                if (!$guest) {
                    return $this->render(
                        'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                        [
                            'wspotNas'      => $nas,
                            'template'      => $template,
                            'form'          => $form->createView(),
                            'error'         => 'wspot.login_page.forgot_pass_guest_not_found',
                            'hasDocument'   => $hasDocument,
                            'hasEmail'      => $hasEmail,
                            'step'          => 'one'
                        ]
                    );
                }

                if ($guest && $guest->getStatus() == Guest::STATUS_INACTIVE) {
                    return $this->render(
                        'FrontendBundle:SignIn:signInInactive.html.twig',
                        [
                            'guest'    => $guest,
                            'template' => $template
                        ]
                    );
                }
            }
            
            // Pega o guest id do mysql vindo do form após escolher o método de recuperação
            if ($forget_password_choice != "" && $request->get('_guest_id')) {
                $mysqlGuestId = (int)base64_decode($request->get("_guest_id", '0'));
                
                $guest = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findOneBy([
                        'mysql' => $mysqlGuestId
                    ]);
            }
            
            $render_secret_answer_recovery = $guest->isHasSecurityAnswer() && $client->hasGuestPasswordRecoverySecurity();
            $render_email_recovery         = $client->getGuestPasswordRecoveryEmail();
            if ($render_secret_answer_recovery && $render_email_recovery && $step == 'one') {
                return $this->render(
                    'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                    [
                        'wspotNas'         => $nas,
                        'template'         => $template,
                        'form'             => $form->createView(),
                        'error'            => $error,
                        'step'             => 'forget-password-choice',
                        '_guest_id'        => base64_encode($guest->getMysql())
                    ]
                );
            } else if ($render_email_recovery && $step == 'one' || $forget_password_choice == 'email') {
                // Faz o desvio do fluxo caso o visitante & cliente tenha a recuperação por email configurada
                $this->guestService->changePassword($nas, $guest, null, true);
                return $this->render(
                    'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                    [
                        'wspotNas'         => $nas,
                        'template'         => $template,
                        'form'             => $form->createView(),
                        'error'            => $error,
                        'step'             => 'email',
                        'guestEmail'       => $guest->getPropertyByKey(Guest::PROPERTY_EMAIL)             
                    ]
                );
            } else if ($render_secret_answer_recovery && $step == 'one' || $forget_password_choice == 'secret-answer') {
                $questionAnswered = $this->secretQuestionManager->getQuestionAnsweredInfo($guest->getMysql());

                return $this->render(
                    'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                    [
                        'wspotNas'         => $nas,
                        'template'         => $template,
                        'form'             => $form->createView(),
                        'error'            => $error,
                        'hasDocument'      => $hasDocument,
                        'hasEmail'         => $hasEmail,
                        'questionAnswered' => $questionAnswered,
                        'step'             => 'step-validate-answer'
                    ]
                );

            }

            $properties = $guest->getProperties();
            $nullCount = 0;
            foreach ($properties as $property) {
                if ($property === null) {
                    $nullCount++;
                }
            }

            if (empty($properties) || $nullCount == count($properties)) {
                $this->guestService->changePassword($nas, $guest, null, true);

                $fromEmail = 'no-reply@wspot.com.br';
                if ($this->configurationService->get($nas, $client, 'from_email')) {
                    $fromEmail = $this->configurationService->get($nas, $client, 'from_email');
                }

                return $this->render(
                    'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                    [
                        'wspotNas'      => $nas,
                        'template'      => $template,
                        'fromEmail'     => $fromEmail,
                        'form'          => $form->createView(),
                        'error'         => $error,
                        'hasDocument'   => $hasDocument,
                        'hasEmail'      => $hasEmail,
                        'step'          => 'auto'
                    ]
                );
            } else {
                $guest->setLocale($request->getLocale());

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
                            'step'       => 'two',
                            'properties' => implode(',', array_keys($properties))
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
                        'step'          => 'two'
                    ]
                );
            }
        }
        return $this->render(
            'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
            [
                'wspotNas'      => $nas,
                'template'      => $template,
                'form'          => $form->createView(),
                'error'         => $error,
                'hasDocument'   => $hasDocument,
                'hasEmail'      => $hasEmail,
                'forgetEmail'   => boolval($hasEmail),
                'step'          => 'one'
            ]
        );
    }

    public function recoveryPasswordAction(Request $request)
    {
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        if (!$nas) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'));
        }

        $hasDocument    = $this->mongo->getRepository('DomainBundle:CustomFields\Field')->hasField('document');
        $guestId        = (int)$request->get('guestId');
        $template       = $this->templateService->templateSettings($this->session->get('campaignId'));
        $data           = $request->request->get('frontend_recovery_password');

        if (!isset($data)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_forget_password'));
        }

        $dataProperties = null;

        if (array_key_exists('properties', $data) == true) {
            $dataProperties = $data['properties'];
        }

        $properties     = $this->replaceProperties($dataProperties);
        $query          = [];
        $query['mysql'] = $guestId;

        foreach ($properties as $key => $value) {
            $helper = StringHelper::accentToRegex($value);
            $query[$key] = new \MongoRegex("/.*{$helper}.*/i");
        }

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy($query)
        ;

        if (!$guest) {
            $form = $this->forgetPasswordForm($guestId);

            return $this->render(
                'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                [
                    'wspotNas'      => $nas,
                    'template'      => $template,
                    'form'          => $form->createView(),
                    'error'         => 'wspot.login_page.forgot_pass_wrong_data',
                    'hasDocument'   => $hasDocument,
                    'step'          => 'two'
                ]
            );
        }

        $checkIsValid = hash_equals(
            $data['password']['first'],
            $data['password']['second']
        );

        if (!$checkIsValid) {
            $form = $this->forgetPasswordForm($guestId);

            return $this->render(
                'FrontendBundle:ForgetPassword:forgetPassword.html.twig',
                [
                    'wspotNas'      => $nas,
                    'template'      => $template,
                    'form'          => $form->createView(),
                    'error'         => 'wspot.login_page.forgot_pass_wrong_data',
                    'hasDocument'   => $hasDocument,
                    'step'          => 'two'
                ]
            );
        }

        $this->guestService->changePassword($nas, $guest, $data['password']['first'], false);

        // Auditoria
        $client = $this->getLoggedClient();
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::guest(), $guest->getMysql())
            ->onTarget(Kinds::guest(), $guest->getMysql())
            ->withType(Events::update())
            ->addDescription(AuditEvent::PT_BR, 'Visitante resetou sua própria senha')
            ->addDescription(AuditEvent::EN_US, 'Visitor reset their own password')
            ->addDescription(AuditEvent::ES_ES, 'El visitante restablece su propia contraseña');
        $this->auditor->push($event);

        if ($guest && $guest->getStatus() == Guest::STATUS_PENDING_APPROVAL) {
            return $this->routerService->forward(
                'wspot.frontend.controller.auth:signUpConfirmationAction',
                [
                    'guest'  => $guest->getMysql(),
                    'resend' => false,
                    'locale' => $request->get('_locale')
                ]
            );
        }

        if ($guest->getEmailIsValid() == false) {
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

        return $this->nasService->process($guest, $nas);
    }

    public function forgetPasswordForm($guestId)
    {
        $guest = $this->mongo
            ->getRepository("DomainBundle:Guest\\Guest")
            ->findOneByMysql((int)$guestId)
        ;

        return $this->controllerHelper->createForm(
            ForgotPasswordType::class,
            null,
            [
                'action'    => $this->controllerHelper->generateUrl(
                    'frontend_recovery_password',
                    [
                        'guestId' => $guest->getMysql()
                    ]
                ),
                'method'    => 'POST',
                'attr'      => [
                    'step' => 'two',
                    'properties' => implode(',', array_keys($guest->getProperties()))
                ]
            ]
        );
    }

    public function replaceProperties($properties)
    {
        $query = [];

        if ($properties) {
            foreach ($properties as $key => $value) {
                if ($key == 'document') {
                    $value = preg_replace('/[^0-9]/', null, $value);
                }
                $query = ['properties.'.$key => $value];
            }
        }

        return $query;
    }

    public function forgetEmailAction()
    {
        $template = $this->templateService->templateSettings($this->session->get('campaignId'));

        return $this->render(
            'FrontendBundle:ForgetPassword:forgetEmail.html.twig',
            [
                'wspotNas' => $this->session->get(Nas::NAS_SESSION_KEY),
                'template' => $template
            ]
        );
    }

    public function checkGuestAction(Request $request)
    {
        $document = $request->get('document');

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                '$or' => [
                    [
                        'properties.document' => $document
                    ],
                    [
                        'properties.document' => preg_replace('/[^0-9]/', null, $document)
                    ]
                ]
            ])
        ;
        
        $value = false;
        if ($guest && array_key_exists('email', $guest->getProperties())) {
            $value = $guest->getProperties()['email'];
        }
        return new JsonResponse(
            [
                'value' => $value
            ]
        );
    }

    public function editAction(Request $request)
    {
         /**
         * @var Nas $nas
         */
        $nas        = $this->session->get(Nas::NAS_SESSION_KEY);
        $client     = $this->getLoggedClient();
        $guestMysql = $request->query->get('guest');

        if (empty($guestMysql)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        /**
         * @var Guest $guest
         */
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql((int) $guestMysql);

        if ($guest === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $fields = $request->query->get('fields');

        if ($fields === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $template = $this->templateService->templateSettings($this->session->get('campaignId'));

        array_push($fields, $request->query->get('origin'));

        /** @var Form $form */
        $form = $this->controllerHelper->createForm(
            ChangeUserDataType::class,
            $guest,
            [
                'action' => $this->controllerHelper->generateUrl(
                    'frontend_edit_data',
                    [
                        'guest'  => $guest->getMysql(),
                        'fields' => $fields
                    ]
                )
            ]
        );

        $form->handleRequest($request);

        $phoneField = 'phone';
        if (array_key_exists('mobile', $guest->getProperties())) {
            $phoneField = 'mobile';
        }

        if ($request->get('invalidNumber') === true) {
            $form->get('properties')
                ->addError(new FormError($this->translator->trans('wspot.change_user_data.invalid_phone')));
        }

        if ($form->isValid()) {
            if (in_array('email', $fields)) {
                $emailValidate = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findLikeEmail($guest->getProperties()['email']);

                if ($emailValidate != null && $emailValidate->getMysql() != $guest->getMysql()) {
                    $form->get('properties')
                        ->addError(new FormError($this->translator->trans('wspot.change_user_data.used_email')));



                    return $this->render(
                        'FrontendBundle:SignUp:changeUserData.html.twig',
                        [
                            'template' => $template,
                            'form'     => $form->createView(),
                            'data'     => $guest
                        ]
                    );
                }
            }

            try {
                FieldsHelper::transformPhoneAndMobileGuest($guest, $form);
                $this->guestService->update($guest);
            } catch (UniqueFieldException $e) {
                $form->get('properties')
                    ->addError(new FormError($this->translator->trans('wspot.login_page.field_already_exists')));

                return $this->render(
                    'FrontendBundle:SignUp:changeUserData.html.twig',
                    [
                        'template' => $template,
                        'form'     => $form->createView(),
                        'data'     => $guest
                    ]
                );
            }

            $success = true;

            if (in_array('welcome', $fields)) {
                try {
                    $params = [
                        'domain'        => $this->getLoggedClient()->getDomain(),
                        'guestId'       => $guest->getId(),
                        'locale'        => $guest->getLocale(),
                        'macAddress'    => $nas->getAccessPointMacAddress(),
                        'loginField'    => $this->customFieldsService->getLoginField()
                    ];

                    $notification = $this
                        ->controllerHelper
                        ->getContainer()
                        ->get(NotificationType::REGISTER);

                    $notification->send($nas, $params);

                    if ($success == true) {
                        return $this->nasService->process($guest, $nas);
                    }
                } catch (InvalidSmsPhoneNumberException $e) {
                    if (in_array($phoneField, $fields)) {
                        $form->get('properties')
                            ->addError(new FormError($this->translator->trans('wspot.change_user_data.invalid_phone')));
                    }

                    if (substr($guest->getProperties()[$phoneField], 0, 1) == 0) {
                        $form->get('properties')
                            ->addError(
                                new FormError(
                                    $this->translator->trans('wspot.change_user_data.invalid_phone_ddd')
                                )
                            );
                    }
                    $success = false;
                }
            }

            try {
                if ($success == true) {
                    if ($this->configurationService->get($nas, $client, 'confirmation_sms')) {
                        if ($guest) {
                            $this->session->set('edit', $guest->getMysql());

                            $confirmationForm = $this->controllerHelper->signUpConfirmationForm($guest);
                            $confirmationForm->handleRequest($request);

                            $confirmationType = 'sms';

                            $params = [
                                'domain'    => $this->getLoggedClient()->getDomain(),
                                'guestId'   => $guest->getId(),
                                'locale'    => $guest->getLocale(),
                                'action'    => NotificationType::CONFIRMATION
                            ];

                            $notificationService = $this
                                ->controllerHelper
                                ->getContainer()
                                ->get(NotificationType::CONFIRMATION);

                            $notificationService->sendSMS($nas, $params);

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
                                    'type'     => $confirmationType,
                                    'resend'   => false,
                                    'smsSend'  => $sendSms
                                ]
                            );
                        }
                    }

                    if ($this->configurationService->get($nas, $client, 'confirmation_email')) {
                        return $this->controllerHelper->redirectToRoute(
                            'frontend_signup_confirmation_action',
                            [
                                'guest'  => $guest->getMysql(),
                                'resend' => false
                            ]
                        );
                    }

                    $params = [
                        'domain'        => $this->getLoggedClient()->getDomain(),
                        'guestId'       => $guest->getId(),
                        'locale'        => $guest->getLocale(),
                        'macAddress'    => $nas->getAccessPointMacAddress()
                    ];

                    $notification = $this
                        ->controllerHelper
                        ->getContainer()
                        ->get(NotificationType::REGISTER);

                    $notification->sendSMS($nas, $params);

                    return $this->nasService->process($guest, $nas);
                }
            } catch (InvalidSmsPhoneNumberException $e) {
                if (in_array($phoneField, $fields)) {
                    $form->get('properties')
                        ->addError(new FormError($this->translator->trans('wspot.change_user_data.invalid_phone')));
                }
                if (substr($guest->getProperties()[$phoneField], 0, 1) == 0) {
                    $form->get('properties')
                        ->addError(new FormError($this->translator->trans('wspot.change_user_data.invalid_phone_ddd')));
                }
                $success = false;
            }

            if ($success == true) {
                return $this->controllerHelper->redirectToRoute(
                    'frontend_signup_confirmation_action',
                    [
                        'guest'  => $guest->getMysql(),
                        'resend' => false
                    ]
                );
            }
        }

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::UPDATE_GUEST_DATA)
            ->withEventType(EventType::UPDATE_GUEST_DATA)
            ->withNas($nas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData(null)
            ->build();

        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:SignUp:changeUserData.html.twig',
            [
                'template' => $template,
                'form'     => $form->createView(),
                'data'     => $guest
            ]
        );
    }

    public function emailUpdateAction(Request $request)
    {
        $guestMysql = $request->query->get('guest');

        if (empty($guestMysql)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        /**
         * @var Guest $guest
         */
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql((int) $guestMysql);

        if ($guest === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        if ($guest->getLoginField() !== 'email') {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $invalidEmail   = $guest->getProperties()['email'];
        $fields         = $request->query->get('fields');
        $template       = $this->templateService->templateSettings($this->session->get('campaignId'));

        array_push($fields, $request->query->get('origin'));

        $form = $this->controllerHelper->createForm(
            ChangeUserDataType::class,
            $guest,
            [
                'action' => $this->controllerHelper->generateUrl(
                    'frontend_email_update',
                    [
                        'guest'  => $guest->getMysql(),
                        'fields' => $fields
                    ]
                )
            ]
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            if (in_array('email', $fields)) {
                $emailValidate = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findLikeEmail($guest->getProperties()['email']);

                if ($emailValidate != null && $emailValidate->getMysql() != $guest->getMysql()) {
                    $form->get('properties')['email']
                        ->addError(new FormError($this->translator->trans('wspot.change_user_data.used_email')));

                    return $this->render(
                        'FrontendBundle:SignIn:invalidEmail.html.twig',
                        [
                            'template' => $template,
                            'form'     => $form->createView(),
                            'data'     => $guest
                        ]
                    );
                }

                $newEmail = $guest->getProperties()['email'];

                if ($newEmail == $invalidEmail) {
                    $form->get('properties')['email']
                        ->addError(new FormError($this->translator->trans('wspot.invalid_email.new_email')));

                    return $this->render(
                        'FrontendBundle:SignIn:invalidEmail.html.twig',
                        [
                            'template' => $template,
                            'form'     => $form->createView(),
                            'data'     => $guest
                        ]
                    );
                }
            }

            $guest->setEmailIsValid(true);
            $guest->setStatus(Guest::STATUS_ACTIVE);

            $this->guestService->update($guest);

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'emailUpdate' => true
            ]));
        }

        return $this->render(
            'FrontendBundle:SignIn:invalidEmail.html.twig',
            [
                'template' => $template,
                'form'     => $form->createView(),
                'data'     => $guest
            ]
        );
    }

    public function resendConfirmationAction(Request $request)
    {
    	/**
	     * @var Client $client
	     */
    	$client = $this->getLoggedClient();
        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($request->get('guest') == null || !$nas) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

	    /**
         * @var Guest $guest
         */
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql((int)$request->get('guest'))
        ;

        if ($guest === null) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        try {
            $params = [
                'domain'        => $client->getDomain(),
                'guestId'       => $guest->getId(),
                'locale'        => $guest->getLocale(),
                'macAddress'    => $nas->getAccessPointMacAddress()
            ];

            $notificationService = $this
                ->controllerHelper
                ->getContainer()
                ->get(NotificationType::CONFIRMATION);

	        if ($this->configurationService->get($nas, $client, 'confirmation_email') == 1) {
	        	$notificationService->send($nas, $params);
		        return $this->controllerHelper->redirect(
			        $this->controllerHelper->generateUrl(
				        'frontend_signup_confirmation_action',
				        [
					        'guest'  => $guest->getMysql(),
					        'resend' => $request->get('mode')
				        ]
			        )
		        );
	        }

	        if ($this->configurationService->get($nas, $client, 'confirmation_sms') == 1) {
		        $notificationService->sendSMS($nas, $params);
		        return $this->controllerHelper->redirect(
			        $this->controllerHelper->generateUrl(
				        'frontend_signup_confirmation_action',
				        [
					        'guest'  => $guest->getMysql(),
					        'resend' => $request->get('mode')
				        ]
			        )
		        );
	        }
        } catch (InvalidSmsPhoneNumberException $e) {
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
        }

	    return new JsonResponse("Success", 200);
    }
}
