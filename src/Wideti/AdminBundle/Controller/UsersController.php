<?php

namespace Wideti\AdminBundle\Controller;

use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\AdminBundle\Form\UsersType;
use Wideti\AdminBundle\Form\CreatePasswordType;
use Wideti\AdminBundle\Form\ForgotPasswordType;
use Wideti\AdminBundle\Form\PasswordForgottenType;
use Wideti\AdminBundle\Form\PerfilPasswordType;
use Wideti\AdminBundle\Form\PerfilUsuarioType;
use Wideti\AdminBundle\Form\Type\User\UserFilterType;
use Wideti\AdminBundle\Helpers\TwoFactorAuthentication;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\Pagination;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\User\UserServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class UsersController
{
    use EntityManagerAware;
    use TwigAware;
    use UserServiceAware;
    use FlashMessageAware;
    use SecurityAware;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;
    /**
     * @var TwoFactorAuthentication
     */
    private $twoFactorAuthenticationService;

    /**
     * UsersController constructor.
     * @param AdminControllerHelper $controllerHelper
     * @param AnalyticsService $analyticsService
     */
    public function __construct(AdminControllerHelper $controllerHelper, AnalyticsService $analyticsService, TwoFactorAuthentication $twoFactorAuthenticationService )
    {
        $this->controllerHelper               = $controllerHelper;
        $this->analyticsService               = $analyticsService;
        $this->twoFactorAuthenticationService = $twoFactorAuthenticationService;
    }

    public function indexAction($page, Request $request)
    {
        $filter = null;
        $value  = null;

        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $formFilter = $this->controllerHelper->createForm(UserFilterType::class);
        $formFilter->handleRequest($request);

        if ($formFilter->isValid()) {
            $dataFilter = $formFilter->getData();
            $filter     = $dataFilter['filtro'];
            $value      = $dataFilter['value'];
        }

        $count  = $this->em
            ->getRepository("DomainBundle:Users")
            ->count($this->getLoggedClient(), $filter, $value, $this->getUser()->getUsername());

        $pagination       = new Pagination($page, $count);
        $pagination_array = $pagination->createPagination();

        $entity_Users = $this->em
            ->getRepository('DomainBundle:Users')
            ->listAllUsers(
                $this->getLoggedClient(),
                $pagination->getPerPage(),
                $pagination_array['offset'],
                $filter,
                $value,
                $this->getUser()->getUsername()
            );

        return $this->render(
            'AdminBundle:Users:index.html.twig',
            [
                'authUser'    => $this->getUser()->getUsername(),
                'users'       => $entity_Users,
                'pagination'  => $pagination_array,
                'form'        => $formFilter->createView(),
                'users_total' => $count
            ]
        );
    }

    /**
     * @ParamConverter(
     *      "user",
     *      class       = "DomainBundle:Users",
     *      options     = {"message" = "Administrador não encontrado."},
     *      isOptional  = true
     * )
     * @param Users|null  $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Users $user)
    {
        if (!$user->getClient()) {
            if ($user->getErpId() && $user->getErpId()  == $this->getLoggedClient()->getErpId()) {
                return $this->render(
                    'AdminBundle:Users:show.html.twig',
                    [
                        'entity' => $user
                    ]
                );
            } else {
                return $this->render("AdminBundle:Admin:notFound.html.twig", [
                    "message" => "Administrador não encontrado."
                ]);
            }
        }
        return $this->render(
            'AdminBundle:Users:show.html.twig',
            [
                'entity' => $user
            ]
        );
    }

    public function createAction(Request $request)
    {
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $user = new Users();
        $loggedUser = $this->getUser();
        
        $options = [
			'new' => true,
			'attr'=> [
				'logged_user' => $loggedUser,
                'is_wspot_domain' => $this->session->get('isRegularDomain'),
			]
		];
        
        $form = $this->controllerHelper->createForm(UsersType::class, $user, $options);
        $form->handleRequest($request);
 
        if ($form->isValid()) {
            $client  = $this->em
                ->getRepository("DomainBundle:Client")
                ->find($this->getLoggedClient());

            if ($client == null) {
                throw new NotFoundHttpException('Client not found');
            }

            $isValid = !$this->userService->userExists($user->getUsername(), $client);

            $user->setClient($client);

            try {
                $this->userService->register($user, $form->getData()->getAutoPassword());
                $this->setCreatedFlashMessage();
            } catch (\Exception $e) {
                if ($e->getMessage() == 'Duplicate entry') {
                    $isValid = false;
                }
            }

            if ($isValid == false) {
                $form->get('username')
                    ->addError(new FormError('E-mail já cadastrado na base de dados.'));
            } else {
                $this->analyticsService->handler($request, true);
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_usuarios'));
            }
        }

        return $this->render(
            'AdminBundle:Users:new.html.twig',
            [
                'Users'      => $user,
                'form'       => $form->createView(),
                'logged_user' => $loggedUser
            ]
        );
    }

    /**
     * @ParamConverter(
     *      "user",
     *      class       = "DomainBundle:Users",
     *      converter   = "client",
     *      options     = {"message" = "Administrador não encontrado."}
     * )
     * @param Request $request
     * @param Users $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(Request $request, Users $user)
    {       
        $client = $this->getLoggedClient();
        PlanAssert::checkOrThrow($client, Plan::PRO);

        $loggedUser = $this->getUser();

        $options = [
        	'attr' => [
        	    'logged_user' => $loggedUser,
                'is_wspot_domain' => $this->session->get('isRegularDomain'),
	        ]
        ];

        $form = $this->controllerHelper->createForm(UsersType::class, $user, $options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->userService->update($user);
            $this->setUpdatedFlashMessage();

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_usuarios'));
        }

        return $this->render(
            'AdminBundle:Users:edit.html.twig',
            [
                'Users'         => $user,
                'edit_form'     => $form->createView(),
                'logged_user'   => $loggedUser
            ]
        );
    }

    /**
     * @ParamConverter(
     *      "user",
     *      class       = "DomainBundle:Users",
     *      converter   = "client",
     *      options     = {"message" = "Administrador não encontrado."}
     * )
     * @param Request $request
     * @param Users $user
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, Users $user)
    {
        try {
            $this->userService->delete($user);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'type'    => 'success',
                        'message' => 'Registro removido com sucesso'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'type'    => 'error',
                    'message' => 'Exclusão não permitida'
                ]
            );
        }
        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_usuarios'));
    }

	/**
	 * @param Request $request
	 * @param $id
	 * @return JsonResponse
	 * @throws NoResultException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
	 */
    public function resetPasswordAction(Request $request, $id)
    {
        if ($this->authorizationChecker->isGranted('ROLE_MARKETING')) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->findOneBy(
                [
                    'id' => $id,
                    'client' => $this->getLoggedClient()
                ]
            );

        if (!$user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['msg' => 'Usuário não encontrado']);
            } else {
                throw new NotFoundHttpException('Usuário não encontrado.');
            }
        }

        $this->userService->requestEditUserPassword($user);
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['msg' => 'Nova senha gerada e enviada para o usuário.']);
        } else {
            return $user->getPassword();
        }
    }

	/**
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws NoResultException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\SendEmailFailException,
	 */
    public function profileEditAction(Request $request)
    {        
        $user = $this->getUser();
        $oldPassword = $user->getPassword();
        $client = $this->getLoggedClient();

        $actionUrl = $this
            ->controllerHelper
            ->generateUrl('admin_profile_edit', ['username' => $user->getUsername()]);
       
        $form   = $this->controllerHelper->createForm(
            PerfilUsuarioType::class,
            $user,
            [
                'action' => $actionUrl,
                'method' => 'POST'
            ]
        );

        $form->handleRequest($request);
               
        if ($form->get('two_factor_authentication_code')->isValid()) {
            if ($form->get('two_factor_authentication_enabled')->getData()) {
                if (!$this->twoFactorAuthenticationService
                    ->checkCode($user, $form->get('two_factor_authentication_code')->getData())) {
                    $form->addError(new FormError("Código de autenticação duas etapas inválido"));
                    $form->isValid(false);
                }
            }
        }
        
        if ($form->isValid()) {
            if ($form->get('two_factor_authentication_enabled')->getData() == '0' ) {
                $user->resetTwoFactorAuthenticationSecret();
            }

            if ($form->get('nome')->isValid()) {
                $this->userService->changeName($user, $form->get('nome')->getData());
            }

            if ($form->get('receive_report_mail')->isValid()) {
                $this->userService->changeReceiveReportMail($user, $form->get('receive_report_mail')->getData());
            }

            $this->setFlashMessage('notice', 'Preferências alteradas com sucesso');

        }

        return $this->render(
            'AdminBundle:Users:profile.html.twig',
            [
                'entity'    => $user,
                'form'      => $form->createView(),
                'qrcodeURL' => $this->twoFactorAuthenticationService
                                    ->generateTwoFactorAuthenticationQRCodeURL($user),
                'error'     => null,
                'isWhiteLabel' => $client->isWhiteLabel(),
            ]
        );
    }

    public function profilePasswordEditAction(Request $request)
    {
        $user = $this->getUser();
        $oldPassword = $user->getPassword();
        $client = $this->getLoggedClient();

        $actionUrl = $this
            ->controllerHelper
            ->generateUrl('admin_profile_password_edit', ['username' => $user->getUsername()]);

        $form = $this->controllerHelper->createForm(
            PerfilPasswordType::class,
            $user,
            [
                'action' => $actionUrl,
                'method' => 'POST'
            ]
        );
        $form->handleRequest($request);
        if ($this->isPasswordEqual($request->get("user_profile")["password"])) {
            $this->setFlashMessage('error', "As duas senhas devem ser iguais.");
        } else if ($form->isValid())  {
            $currentPasswordValid = $this->userService->currentPasswordIsValid($user, $oldPassword, $request->get("current_password"));
            if (!$currentPasswordValid) {
                $this->setFlashMessage('error2', "Senha atual incorreta.");
            } else {
                $strongPassword = $this->userService->validateStrongPassword($form->get('password')->getData());
                if ($this->isValidPassword($form->get('password')->isValid(), $strongPassword)) {
                    $this->userService->changePassword($user, $form->get('password')->getData(), $oldPassword);
                    $this->setFlashMessage('notice', 'Senha alterada com sucesso.');
                } else if (!$strongPassword) {
                    $this->setFlashMessage('error', "A senha nao corresponde aos requisitos abaixo.");
                }
            }
        }

        return $this->render(
            'AdminBundle:Users:profilePassword.html.twig',
            [
                'entity'    => $user,
                'form'      => $form->createView(),
                'qrcodeURL' => $this->twoFactorAuthenticationService
                                    ->generateTwoFactorAuthenticationQRCodeURL($user),
                'error'     => null,
                'isWhiteLabel' => $client->isWhiteLabel(),
            ]
        );
    }

    private function isPasswordEqual($passwords)
    {
        return $passwords["first"] !== $passwords["second"];
    }

    public function checkIfExistsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();

            $email = $request->request->get('email');

            $entity = $em->getRepository('DomainBundle:Users')
                ->findOneBy([
                    'username' => $email,
                    'client' => $this->getLoggedClient()
                ]);

            if ($entity) {
                $return = [ "exists" => true, "message" => "E-mail já cadastrado na base de dados." ];
            } else {
                $return = [ "exists" => false ];
            }

            return new JsonResponse($return);
        }
    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function resetForgottenPasswordAction(Request $request)
    {
        $urlDecoded = base64_decode($request->get("url"));
        $aux = explode(",", $urlDecoded);
        $userId = $aux[0];
        $urlPath = $aux[1];
        $user = $this->em
        ->getRepository('DomainBundle:Users')
        ->findOneBy(
            [
                'id' => $userId
            ]
        );

        $oldPassword = $user->getPassword();
        $client = $this->getLoggedClient();

        $actionUrl = $this
            ->controllerHelper
            ->generateUrl('admin_profile_password_edit');

        $form = $this->controllerHelper->createForm(
            PerfilPasswordType::class,
            $user,
            [
                'action' => $actionUrl,
                'method' => 'POST'
            ]
        );
        $resetValid = $this->userService->resetPasswordIsValid($user, $urlPath);
        if (!$resetValid) {
            return $this->render(
                'AdminBundle:Users:urlPasswordExpired.html.twig',
                [
                    'entity'    => $user,
                    'form'      => $form->createView(),
                    'qrcodeURL' => $this->twoFactorAuthenticationService
                        ->generateTwoFactorAuthenticationQRCodeURL($user),
                    'error'     => null,
                    'isWhiteLabel' => $client->isWhiteLabel(),
                ]
            );
        }
        $form->handleRequest($request);
        $success = false;
        if ($this->isPasswordEqual($request->get("user_profile")["password"])) {
            $this->setFlashMessage('error', "As duas senhas devem ser iguais.");
        } else if ($form->isSubmitted()) {
            $strongPassword = $this->userService->validateStrongPassword($form->get('password')->getData());
            if ($this->isValidPassword($form->get('password')->isValid(), $strongPassword)) {
                $this->userService->resetForgottenPassword($user, $form->get('password')->getData(),  $urlPath,$oldPassword);
                $success = true;
            } else if (!$strongPassword) {
                $this->setFlashMessage('error', "A senha não corresponde aos requisitos abaixo.");
            }
        }

        return $this->render(
            'AdminBundle:Users:passwordForgotten.html.twig',
            [
                'entity'    => $user,
                'form'      => $form->createView(),
                'qrcodeURL' => $this->twoFactorAuthenticationService
                                    ->generateTwoFactorAuthenticationQRCodeURL($user),
                'error'     => null,
                'isWhiteLabel' => $client->isWhiteLabel(),
                'success' => $success
            ]
        );
    }

    public function createFirstPasswordAction(Request $request)
    {
        $urlDecoded = base64_decode($request->get("url"));
        $aux = explode(",", $urlDecoded);
        $userId = $aux[0];
        $urlPath = $aux[1];
        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->findOneBy(
                [
                    'id' => $userId
                ]
            );

        $oldPassword = $user->getPassword();
        $client = $this->getLoggedClient();

        $actionUrl = $this
            ->controllerHelper
            ->generateUrl('admin_profile_password_edit');
        $form = $this->controllerHelper->createForm(
            PerfilPasswordType::class,
            $user,
            [
                'action' => $actionUrl,
                'method' => 'POST'
            ]
        );
        $resetValid = $this->userService->resetUrlIsValid($user, $urlPath);
        if (!$resetValid) {
            return $this->render(
                'AdminBundle:Users:urlPasswordExpired.html.twig',
                [
                    'entity' => $user,
                    'form' => $form->createView(),
                    'qrcodeURL' => $this->twoFactorAuthenticationService
                        ->generateTwoFactorAuthenticationQRCodeURL($user),
                    'error' => null,
                    'isWhiteLabel' => $client->isWhiteLabel(),
                ]
            );
        }
        $form->handleRequest($request);
        $success = false;
        if ($this->isPasswordEqual($request->get("user_profile")["password"])) {
            $this->setFlashMessage('error', "As duas senhas devem ser iguais.");
        } else if ($form->isSubmitted()) {
            $strongPassword = $this->userService->validateStrongPassword($form->get('password')->getData());
            if ($this->isValidPassword($form->get('password')->isValid(), $strongPassword)) {
                $this->userService->createFirstPassword($user, $form->get('password')->getData(), $urlPath);
                $success = true;
            } else if (!$strongPassword) {
                $this->setFlashMessage('notice', "A senha não corresponde aos requisitos abaixo.");
            }
        }
        return $this->render(
            'AdminBundle:Users:createFirstPassword.html.twig',
            [
                'entity'    => $user,
                'form'      => $form->createView(),
                'qrcodeURL' => $this->twoFactorAuthenticationService
                    ->generateTwoFactorAuthenticationQRCodeURL($user),
                'error'     => null,
                'isWhiteLabel' => $client->isWhiteLabel(),
                'success' => $success
            ]
        );
    }
    public function forgotPasswordAction(Request $request)
    {
        $form = $this->controllerHelper->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $form->getData();
            try {
                $this->userService->forgotPassword($user->getUsername());
            } catch (NoResultException $e) {
            }
            $this->setFlashMessage(
                'success', 'Se o e-mail existir, um link de redefinição de senha será enviado.'
            );
        }

        return $this->render(
            'AdminBundle:Users:changePassword.html.twig',
            [
                'form'  => $form->createView()
            ]
        );
    }

    public function verifyIfResetedToStrongPasswordAction(Request $request)
    {
        $user = $this->em
        ->getRepository('DomainBundle:Users')
        ->findOneBy(
            [
                'id' => $this->getUser()->getId()
            ]
        );

        $oldPassword = $user->getPassword();
        $client = $this->getLoggedClient();

        $actionUrl = $this
            ->controllerHelper
            ->generateUrl('admin_profile_password_edit');
        $form = $this->controllerHelper->createForm(
            PerfilPasswordType::class,
            $user,
            [
                'action' => $actionUrl,
                'method' => 'POST'
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $strongPassword = $this->userService->validateStrongPassword($form->get('password')->getData());
            if ($this->isValidPassword($form->get('password')->isValid(), $strongPassword)) {
                $this->userService->resetPasswordToStrong($user, $form->get('password')->getData(), $oldPassword);
                $this->setFlashMessage('notice', 'Senha alterada com sucesso');
            } else if (!$strongPassword) {
                $this->setFlashMessage('notice', "Senha não contem os critérios para ser considerada forte.");
            }
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('admin_dashboard'));
        }

        return $this->render(
            'AdminBundle:Users:passwordToStrong.html.twig',
            [
                'entity'    => $user,
                'form'      => $form->createView(),
                'qrcodeURL' => $this->twoFactorAuthenticationService
                                    ->generateTwoFactorAuthenticationQRCodeURL($user),
                'error'     => null,
                'isWhiteLabel' => $client->isWhiteLabel(),
            ]
        );
    }

    private function isValidPassword($isValid, $strongPassword)
    {
        return $isValid && $strongPassword;
    }

    public function resetUserPasswordAction(Request $request)
    {
        $urlDecoded = base64_decode($request->get("url"));
        $aux = explode(",", $urlDecoded);
        $userId = $aux[0];
        $urlPath = $aux[1];
        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->findOneBy(
                [
                    'id' => $userId
                ]
            );

        $oldPassword = $user->getPassword();
        $client = $this->getLoggedClient();

        $actionUrl = $this
            ->controllerHelper
            ->generateUrl('admin_profile_password_edit');
        $form = $this->controllerHelper->createForm(
            PerfilPasswordType::class,
            $user,
            [
                'action' => $actionUrl,
                'method' => 'POST'
            ]
        );
        $resetValid = $this->userService->resetUrlIsValid($user, $urlPath);
        if (!$resetValid) {
            return $this->render(
                'AdminBundle:Users:urlPasswordExpired.html.twig',
                [
                    'entity' => $user,
                    'form' => $form->createView(),
                    'qrcodeURL' => $this->twoFactorAuthenticationService
                        ->generateTwoFactorAuthenticationQRCodeURL($user),
                    'error' => null,
                    'isWhiteLabel' => $client->isWhiteLabel(),
                ]
            );
        }
        $form->handleRequest($request);
        $success = false;
        if ($this->isPasswordEqual($request->get("user_profile")["password"])) {
            $this->setFlashMessage('error', "As duas senhas devem ser iguais.");
        } else if ($form->isSubmitted()) {
            $strongPassword = $this->userService->validateStrongPassword($form->get('password')->getData());
            if ($this->isValidPassword($form->get('password')->isValid(), $strongPassword)) {
                $this->userService->resetForgottenPassword($user, $form->get('password')->getData(), $urlPath, $oldPassword);
                $success = true;
            } else if (!$strongPassword) {
                $this->setFlashMessage('notice', "Senha não contem os critérios para ser considerada forte.");
            }
        }

        return $this->render(
            'AdminBundle:Users:resetOtherUserPassword.html.twig',
            [
                'entity'    => $user,
                'form'      => $form->createView(),
                'qrcodeURL' => $this->twoFactorAuthenticationService
                    ->generateTwoFactorAuthenticationQRCodeURL($user),
                'error'     => null,
                'isWhiteLabel' => $client->isWhiteLabel(),
                'success' => $success
            ]
        );
    }
}