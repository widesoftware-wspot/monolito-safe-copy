<?php

namespace Wideti\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class FirstConfigController
{
    use TwigAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;

    public function __construct(FrontendControllerHelper $controllerHelper)
    {
        $this->controllerHelper = $controllerHelper;
    }

    public function blockAction()
    {
        return $this->render('@Frontend/FirstConfig/block.twig');
    }

    public function apBadParameterErrorAction(Request $request)
    {
        $message = $request->get('message');
        return $this->render('@Frontend/Nas/wrongParametersError.twig', ['message' => $message]);
    }

    public function apNotRegisteredErrorAction(Request $request)
    {
        $message = $request->get('message');
        return $this->render('@Frontend/Nas/apNotRegisteredError.twig', ['message' => $message]);
    }
}
