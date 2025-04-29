<?php

namespace Wideti\DomainBundle\Helpers\Controller;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wideti\DomainBundle\Entity\Users;

interface AdminControllerHelper extends ControllerHelper
{
    /**
     * @return Users
     */
    public function getUser();
}
