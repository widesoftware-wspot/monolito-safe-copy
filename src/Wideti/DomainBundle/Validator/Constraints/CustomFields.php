<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description
 * @author Leonardo
 *
 * @Annotation
 */
class CustomFields extends Constraint
{
    public $message = 'wspot.signup_page.field_custom_required';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}