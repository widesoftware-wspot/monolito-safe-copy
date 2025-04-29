<?php
/**
 * Created by PhpStorm.
 * User: evandro
 * Date: 06/03/19
 * Time: 17:13
 */

namespace Wideti\DomainBundle\Service\User;


use Symfony\Component\HttpFoundation\Request;

interface CredentialCheckService
{
    public function check(Request $request);
}