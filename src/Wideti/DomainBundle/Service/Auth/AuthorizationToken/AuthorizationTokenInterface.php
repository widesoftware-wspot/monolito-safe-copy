<?php


namespace Wideti\DomainBundle\Service\Auth\AuthorizationToken;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Entity\Users;

interface AuthorizationTokenInterface
{
    /**
     * @param Users $user
     * @return Users
     */
    public function create(Users $user);

    public function saveOnSession($token);

    public function saveOnCookie(Request $request, Response $response);

    public function removeCookie(RedirectResponse $response, $host);
}