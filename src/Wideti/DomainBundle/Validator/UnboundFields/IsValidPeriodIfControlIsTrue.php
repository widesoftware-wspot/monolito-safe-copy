<?php

namespace Wideti\DomainBundle\Validator\UnboundFields;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;


/**
 * Esta validação valida o formato:
 * 1d 12h 5m que é usado na definição de tempo de acesso
 *
 */
class IsValidPeriodIfControlIsTrue implements UnboundControlFieldConstraintInterface
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

        if (!$this->isValidPeriod($fieldToValidate)) {
            $form[$this->fieldName]->addError(new FormError($this->errorMessage));
        }
    }

    private function isValidPeriod($period)
    {
        $timeFragments = explode(" ", $period);
        $pattern = "/^[0-9]{1,3}[d,D,h,H,m,M,s,S]{1}$/";

        if (count($timeFragments) > 4) {
            return false;
        }

        foreach ($timeFragments as $fragment) {
            if (!preg_match($pattern, $fragment)) {
                return false;
            }
        }
        return true;
    }
}
