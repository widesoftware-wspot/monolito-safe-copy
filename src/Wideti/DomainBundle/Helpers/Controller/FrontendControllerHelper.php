<?php

namespace Wideti\DomainBundle\Helpers\Controller;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Document\Guest\Guest;

interface FrontendControllerHelper extends ControllerHelper
{
    public function signUpConfirmationForm(Guest $guest);
    public function signInForm(Request $request);
    public function signUpForm(Request $request, $authorizeEmail, $apGroupId);
    public function setTwigGlobalVariable($key, $value);
}
