<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine;
use Symfony\Component\HttpFoundation\Response;

/**
 * Symfony Server Setup: - [ setTemplate, ["@templating"] ]
 */
trait TwigAware
{
    /**
     * @var TimedTwigEngine
     */
    protected $twig;

    public function setTemplate($twig)
    {
        $this->twig = $twig;
    }

    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->twig->renderResponse($view, $parameters, $response);
    }

    public function renderView($view, array $parameters = array())
    {
        return $this->twig->render($view, $parameters);
    }
}
