<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Symfony Server Setup: - [ setRouter, ["@router"] ]
 */
trait RouterAware
{
    /**
     * @var Router
     */
    protected $router;

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }
}
