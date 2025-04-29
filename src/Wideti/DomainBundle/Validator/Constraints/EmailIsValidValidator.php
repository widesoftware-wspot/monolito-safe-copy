<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Validator\EmailValidate;

class EmailIsValidValidator extends ConstraintValidator
{
    use TranslatorAware;

    public function validate($email, Constraint $constraint)
    {
        $emailValidate = new EmailValidate();

        if ($emailValidate->validate($email)) {
            $this->context->addViolation($this->translator->trans($constraint->message));
        }
    }
}
