<?php

namespace Wideti\DomainBundle\Validator\UnboundFields;

use Symfony\Component\Form\FormEvent;

interface UnboundControlFieldConstraintInterface
{
    public function __construct($fieldName, $controlFieldName, $errorMessage);
    public function __invoke(FormEvent $event);
}
