<?php

namespace Wideti\DomainBundle\Validator\UnboundFields;

use DateTime;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;

class IsValidDateTimeIfControlIsTrue implements UnboundControlFieldConstraintInterface
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

        if (!$this->isValidDate($fieldToValidate)) {
            $form[$this->fieldName]->addError(new FormError($this->errorMessage));
        }
    }

    private function isValidDate($date, $format = 'd/m/Y H:i', $strict = true)
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        if (!$dateTime || $dateTime->format('Y-m-d H:i:s') < date('Y-m-d H:i:s')) {
            return false;
        }

        if ($strict) {
            $errors = DateTime::getLastErrors();
            if (!empty($errors['warning_count'])) {
                return false;
            }
        }

        return true;
    }
}
