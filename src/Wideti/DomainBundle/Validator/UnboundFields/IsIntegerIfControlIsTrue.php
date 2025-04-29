<?php

namespace Wideti\DomainBundle\Validator\UnboundFields;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;

class IsIntegerIfControlIsTrue implements UnboundControlFieldConstraintInterface
{
    private $fieldName;
    private $controlFieldName;
    private $errorMessage;

    public function __construct($fieldName, $controlFieldName, $errorMessage)
    {
        $this->fieldName = $fieldName;
        $this->controlFieldName = $controlFieldName;
        $this->errorMessage = $errorMessage;
    }

    public function __invoke(FormEvent $event)
    {
        $form = $event->getForm();
        $fieldToValidate = $form->get($this->fieldName)->getData();
        $controlField = $form->get($this->controlFieldName)->getData();

        if (empty($controlField)) {
            return;
        }

        if (!$this->isInt($fieldToValidate)) {
            $form[$this->fieldName]->addError(new FormError($this->errorMessage));
        }
    }

    private function isInt($value)
    {
        $pattern = "/^[0-9]+$/";
        return preg_match($pattern, $value);
    }
}
