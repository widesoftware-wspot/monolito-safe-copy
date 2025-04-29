<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Validator\DomainEmailValidate;

class DomainEmailIsValidValidator extends ConstraintValidator
{
    use TranslatorAware;

    public function validate($email, Constraint $constraint)
    {
        $domainEmailValidate = new DomainEmailValidate();

        if (!$domainEmailValidate->validate($email)) {
            $this->context->addViolation($this->translator->trans($constraint->message));
        }
    }
}
