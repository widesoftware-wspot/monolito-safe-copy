<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Wideti\DomainBundle\Document\Guest\Guest;

/**
 * @Annotation
 */
class AuthCode extends Constraint
{

    public $guest;
    public $message = 'wspot.auth_code.invalid';

    public function validatedBy()
    {
        return 'auth_code_validator';
    }

    public function __construct(Guest $guest)
    {
        $this->guest = $guest;
    }
}
