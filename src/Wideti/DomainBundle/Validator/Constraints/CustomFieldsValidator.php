<?php

namespace Wideti\DomainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Validator\CustomFieldsValidate;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class CustomFieldsValidator extends ConstraintValidator
{
    use MongoAware;
    use TranslatorAware;
    use SessionAware;

    public function validate($properties, Constraint $constraint)
    {
        foreach ($properties as $key => $value) {
            /**
             * @var Field $field
             */
            $field = $this->mongo
                ->getRepository('DomainBundle:CustomFields\Field')
                ->findOneBy([
                    'identifier' => $key
                ])
            ;

            if ($field && $field->getValidations()) {
                foreach ($field->getValidations() as $validations) {
                    if ($validations['type'] == 'required' && $validations['value'] == true && $value === null) {
                        $validation = new CustomFieldsValidate();

                        $errorMessage = explode(' ', $this->translator->trans($constraint->message));
                        $errorMessage = $errorMessage[0] . " " . $field->getNameByLocale($this->session->get('locale')) . " " . $errorMessage[1];

                        if (!$validation->validate($properties)) {
                            $this->context->buildViolation($errorMessage)
                                ->addViolation();
                        }
                    }
                }
            }
        }
    }
}
