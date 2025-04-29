<?php
namespace Wideti\PanelBundle\Controller;

use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\AdminBundle\Form\ForgotPasswordType;
use Wideti\AdminBundle\Form\PanelUsersType;
use Wideti\AdminBundle\Form\Type\User\UserFilterType;
use Wideti\AdminBundle\Form\UsersType;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Repository\UsersRepository;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\User\UserService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

/**
 * Class UserController
 * @package Wideti\PanelBundle\Controller
 */
class UserController
{
    use EntityManagerAware;
    use TwigAware;
    use PaginatorAware;
    use FlashMessageAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var UsersRepository
     */
    private $usersRepository;
    /**
     * @var Users
     */
    private $user;
    /**
     * @var UsersType
     */
    private $usersType;
    /**
     * @var UserFilterType
     */
    private $userFilterType;

    /**
     * UserController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param UserService $userService
     * @param UsersRepository $usersRepository
     */
    public function __construct(
        ConfigurationService     $configurationService,
        FrontendControllerHelper $controllerHelper,
        UserService              $userService,
        UsersRepository          $usersRepository
    )
    {
        $this->configurationService = $configurationService;
        $this->controllerHelper     = $controllerHelper;
        $this->userService          = $userService;
        $this->usersRepository      = $usersRepository;
        $this->user                 = new Users();
        $this->usersType            = PanelUsersType::class;
        $this->userFilterType       = UserFilterType::class;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $filterForm = $this->prepareFilterForm($request);

        return $this->render('PanelBundle:Users:index.html.twig', [
            'filter'   => $filterForm->createView(),
            'entities' => $this->getPagination($request, $filterForm),
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $form = $this->prepareUserForm($request);

        if ($this->validFormAction($form, 'createUserOnPanel', 'setCreatedFlashMessage')) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_user_list'));
        }

        return $this->render('PanelBundle:Users:new.html.twig', [
            'entity' => $this->user,
            'form'   => $form->createView(),
            'role'   => Users::ROLE_MANAGER
        ]);
    }

    /**
     * @param Users $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Users $user, Request $request)
    {
        $this->user = $user;

        $editForm = $this->prepareUserForm($request, 'password');

        if ($this->validFormAction($editForm, 'update', 'setUpdatedFlashMessage')) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_user_list'));
        }

        return $this->render('PanelBundle:Users:edit.html.twig', [
            'entity'    => $this->user,
            'edit_form' => $editForm->createView(),
            'role'      => Users::ROLE_MANAGER
        ]);
    }

    /**
     * @param Users $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeStatusAction(Users $user)
    {
        $user->setStatus(($user->getStatus() == Users::ACTIVE) ? Users::INACTIVE : Users::ACTIVE);
        $this->userService->update($user);

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('panel_user_list'));
    }

    /**
     * @param Request $request
     * @param null $removeField
     * @return \Symfony\Component\Form\FormInterface
     */
    public function prepareUserForm(Request $request, $removeField = null)
    {
        $form = $this->controllerHelper->createForm($this->usersType, $this->user);

        if ($removeField != null)
            $form->remove($removeField);

        $form->handleRequest($request);

        return $form;
    }

    public function forgotPasswordAction(Request $request)
    {
        $form = $this->controllerHelper->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $form->getData();
            try {
                $this->userService->forgotUserPanelPassword($user->getUsername());
            } catch (NoResultException $e) {
            }
            $this->setFlashMessage(
                'success', 'Se o e-mail existir, um link de redefinição de senha será enviado.'
            );
        }

        return $this->render(
            'AdminBundle:Users:changeUserPanelPassword.html.twig',
            [
                'form'  => $form->createView()
            ]
        );
    }

    /**
     * @param $form
     * @param $userServiceMethod
     * @param $flashMessageMethod
     * @return bool
     */
    public function validFormAction($form, $userServiceMethod, $flashMessageMethod)
    {
        if ($form->isValid()) {
            $this->userService->$userServiceMethod($this->user);
            $this->$flashMessageMethod();

            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Form\FormInterface
     */
    public function prepareFilterForm(Request $request)
    {
        $filterForm = $this->controllerHelper->createForm($this->userFilterType);
        $filterForm->handleRequest($request);

        return $filterForm;
    }

    /**
     * @param Request $request
     * @param $form
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getPagination(Request $request, $form)
    {
        $condition = ['role' => Users::ROLE_MANAGER];

        if ($form->isValid()) {
            $filterField = $form->get('filtro')->getData();

            if ($filterField == 'email') {
                $filterField = 'username';
            }

            $condition[$filterField] = $form->get('value')->getData();
        }

        return $this->paginator->paginate(
            $this->em->getRepository('DomainBundle:Users')->findBy($condition),
            $request->query->getInt('page', '1'),
            20
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkEmailAction(Request $request)
    {
        $role = $this->em
            ->getRepository('DomainBundle:Roles')
            ->findOneBy([ 'id' => Users::ROLE_MANAGER ]);

        return new JsonResponse([
            'value' => !($this->userService->checkUserEmailAndRoleExists($request->get('mail'), $role))
        ]);
    }
}