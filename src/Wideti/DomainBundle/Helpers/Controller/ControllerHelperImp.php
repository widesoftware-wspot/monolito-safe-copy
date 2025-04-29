<?php

namespace Wideti\DomainBundle\Helpers\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\FrontendBundle\Form\SignInType;
use Wideti\FrontendBundle\Form\SignUpType;
use Wideti\FrontendBundle\Form\SignUpConfirmationType;

class ControllerHelperImp implements FrontendControllerHelper, AdminControllerHelper
{
    /**
     * @var FormFactory
     */
    private $form;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Router
     */
    private $router;

    public function __construct(
        FormFactory $form,
        ContainerInterface $container,
        Router $router
    ) {
        $this->form = $form;
        $this->container = $container;
        $this->router = $router;
    }

    /**
     * @param $type
     * @param null $data
     * @param array $options
     * @return FormInterface
     */
    public function createForm($type, $data = null, array $options = [])
    {
        return $this->form->create($type, $data, $options);
    }

    public function createFormBuilder($data = null, array $options = [])
    {
        return $this->form->createBuilder('form', $data, $options);
    }

    public function getFormErrors(FormInterface $form)
    {
        $returnErrors   = [];
        $all            = $form->all();

        foreach ($all as $child) {
            foreach ($child->getErrors() as $error) {
                $returnErrors[ $child->getName() ] = $error->getMessageTemplate();
            }
        }

        return $returnErrors;
    }

    public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * @param $url
     * @param int $status
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    public function redirectToRoute($route, array $parameters = [], $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory()
    {
        return $this->form;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @return Users
     */
    public function getUser() {
        $container = $this->getContainer();

        if (!$container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException("Access Denied in retrieve user.");
        }

        return $container->get('security.token_storage')->getToken()->getUser();
    }

    public function signUpConfirmationForm(Guest $guest)
    {
        return $this->createForm(
            SignUpConfirmationType::class,
            $guest,
            [
                'action' => $this->generateUrl(
                    'frontend_signup_confirmation_action',
                    [
                        'guest' => $guest->getMysql()
                    ]
                ),
                'method' => 'POST'
            ]
        );
    }

    public function signInForm(Request $request)
    {
        $guest = new Guest();
        $guest->setLocale($request->getLocale());

        return $this->createForm(
            SignInType::class,
            $guest,
            [
                'action' => $this->generateUrl('frontend_signin_action'),
                'method' => 'POST'
            ]
        );
    }

    public function signUpForm(Request $request, $authorizeEmail,$apGroupId)
    {
        $validationGroups = [];

        $guest = new Guest();
        $guest->setLocale($request->getLocale());

        if ($authorizeEmail) {
            array_push($validationGroups, 'authorize_email');
        }

        return $this->createForm(
            SignUpType::class,
            $guest,
            [
                'action'    => $this->generateUrl('frontend_signup_action'),
                'method'    => 'POST',
                'attr'      => $validationGroups,
                'apGroupId'    => $apGroupId
            ]
        );
    }

    public function setTwigGlobalVariable($key, $value)
    {
        $this->container->get('twig')->addGlobal($key, $value);
    }

    public function getNotFound404Response()
    {
    	throw new NotFoundHttpException();
    }

    /**
     * @param $clientDomain
     * @return string
     */
    public function getRedirectUrlFromGoogleToClientDomain($clientDomain)
    {
        $googleUrl  = $this->container->getParameter('google_url_protocol');
        $googleUrl .= $clientDomain;

        return $googleUrl.$this->container->getParameter('google_url_wspot_callback');
    }

    public function getRedirectUrlToClientDomain($clientDomain)
    {
        return $this->container->getParameter('empty_nas_url_redirect');
    }
}
