<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description
 * @author Leonardo
 *
 * @Annotation
 */
class ClientDomainIsValid extends Constraint
{

    public $message = 'Domínio não permitido';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}