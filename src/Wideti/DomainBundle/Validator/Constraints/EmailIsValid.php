<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description
 * @author Leonardo
 *
 * @Annotation
 */
class EmailIsValid extends Constraint
{
    public $message = 'wspot.signup_page.field_valid_email';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
