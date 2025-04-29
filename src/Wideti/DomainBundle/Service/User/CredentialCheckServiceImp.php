<?php
/**
 * Created by PhpStorm.
 * User: evandro
 * Date: 06/03/19
 * Time: 17:14
 */

namespace Wideti\DomainBundle\Service\User;


use Wideti\DomainBundle\Repository\UsersRepository;
use Symfony\Component\HttpFoundation\Request;
use Wideti\WebFrameworkBundle\Service\PasswordService;

class CredentialCheckServiceImp implements CredentialCheckService
{
    /**
     * @var UsersRepository
     */
    private $usersRepository;
    /**
     * @var PasswordService
     */
    private $passwordService;

    public function __construct(UsersRepository $usersRepository, PasswordService $passwordService)
    {
        $this->usersRepository = $usersRepository;
        $this->passwordService = $passwordService;
    }

    public function check(Request $request)
    {
        $credentials = $this->getCredentials($request);
        $user = $this->usersRepository->findBy(['username'=>$credentials['username']]);
        if (count($user) == 1) {
            $password = $this->passwordService->encodePassword($user[0], $credentials['password']);

            if ($credentials['username'] == $user[0]->getUsername() && $password == $user[0]->getPassword()) {
                return true;
            }
        }

        return false;
    }

    private function getCredentials(Request $request)
    {
        return array (
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password')
        );
    }

}