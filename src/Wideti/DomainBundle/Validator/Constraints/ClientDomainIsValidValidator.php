<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Wideti\DomainBundle\Validator\ClientDomainValidate;

class ClientDomainIsValidValidator extends ConstraintValidator
{
    public function validate($domain, Constraint $constraint)
    {
        $domainValidate = new ClientDomainValidate();

        if (!$domainValidate->validate($domain)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
