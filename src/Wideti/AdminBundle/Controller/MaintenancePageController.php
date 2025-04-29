<?php

namespace Wideti\AdminBundle\Controller;

use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class MaintenancePageController
{
    use TwigAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    public function __construct(AdminControllerHelper $controllerHelper)
    {
        $this->controllerHelper = $controllerHelper;
    }

    public function indexAction()
    {
        return $this->render(
            'AdminBundle:Admin:maintenancePage.html.twig'
        );
    }
}
