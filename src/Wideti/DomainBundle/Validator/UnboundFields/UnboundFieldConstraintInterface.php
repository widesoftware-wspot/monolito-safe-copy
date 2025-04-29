<?php

namespace Wideti\DomainBundle\Validator\UnboundFields;

use Symfony\Component\Form\FormEvent;

interface UnboundFieldConstraintInterface
{
    public function __construct($fieldName, $errorMessage);
    public function __invoke(FormEvent $event);
}
