<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Horizontal16p9 extends Constraint
{
    public $message = 'Dimensões não corretas para a imagem';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}