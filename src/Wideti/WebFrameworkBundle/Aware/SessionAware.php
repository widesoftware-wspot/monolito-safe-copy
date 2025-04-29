<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;

/**
 * Symfony Server Setup: - [ setSession, ["@session"] ]
 */
trait SessionAware
{
    /**
     * @var Session
     */
    protected $session;

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return Client
     */
    public function getLoggedClient()
    {
        return $this->session->get('wspotClient');
    }

    public function addTrace($trace)
    {
        $traceUser = $this->session->get("traceUser");

        if ($traceUser === null) {
            $traceUser = [];
        }

        $trace["client"] = $this->getLoggedClient();

        array_push(
            $traceUser,
            $trace
        );
        $this->session->set("traceUser", $traceUser);
    }

    public function getTrace()
    {
        return $this->session->get("traceUser");
    }
}
