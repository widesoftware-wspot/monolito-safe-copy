<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Wideti\DomainBundle\Service\AuthorizationCode\AuthorizationCodeServiceAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;

class AuthCodeValidator extends ConstraintValidator
{
    use AuthorizationCodeServiceAware;
    use TranslatorAware;

    public function validate($value, Constraint $constraint)
    {
        $guest_code = $this->authorizationCodeService->get($constraint->guest);
        if (!$guest_code || ($guest_code->getCode() != $value)) {
            $this->context->buildViolation($this->translator->trans($constraint->message))
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}