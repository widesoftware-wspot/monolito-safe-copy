<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Vertical9p16 extends Constraint
{
    public $message = 'Dimensões não corretas para a imagem';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}