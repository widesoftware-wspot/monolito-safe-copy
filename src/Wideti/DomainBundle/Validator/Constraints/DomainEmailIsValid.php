<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description
 * @author Leonardo
 *
 * @Annotation
 */
class DomainEmailIsValid extends Constraint
{
    public $message = 'wspot.signup_page.field_valid_domain_email';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}