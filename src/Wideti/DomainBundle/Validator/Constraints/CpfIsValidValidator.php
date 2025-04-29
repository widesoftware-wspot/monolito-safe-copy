<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Wideti\DomainBundle\Validator\CpfValidate;

class CpfIsValidValidator extends ConstraintValidator
{
    public function validate($cpf, Constraint $constraint)
    {
        $cpfValidate = new CpfValidate();

        if (!$cpfValidate->validate($cpf)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
