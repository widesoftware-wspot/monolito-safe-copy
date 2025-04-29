<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description
 * @author Sergio
 *
 * @Annotation
 */
class CpfIsValid extends Constraint
{
    public $message = 'CPF Inválido';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}