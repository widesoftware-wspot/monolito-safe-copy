<?php

namespace Wideti\DomainBundle\Helpers\Controller;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wideti\DomainBundle\Entity\Client;

interface ControllerHelper
{
    /**
     * @param $type
     * @param null $data
     * @param array $options
     * @return FormInterface
     */
    public function createForm($type, $data = null, array $options = []);
    public function createFormBuilder($data = null, array $options = []);
    public function getFormErrors(FormInterface $form);
    public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH);
    public function setTwigGlobalVariable($key, $value);
    /**
     * @param $url
     * @param int $status
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302);
    public function redirectToRoute($route, array $parameters = [], $status = 302);
    /**
     * @return FormFactory
     */
    public function getFormFactory();

    /**
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * @return Router
     */
    public function getRouter();

    public function getNotFound404Response();

    /**
     * @param $clientDomain
     * @param Client $client
     * @return string
     */
    public function getRedirectUrlFromGoogleToClientDomain($clientDomain);

    public function getRedirectUrlToClientDomain($clientDomain);
}
