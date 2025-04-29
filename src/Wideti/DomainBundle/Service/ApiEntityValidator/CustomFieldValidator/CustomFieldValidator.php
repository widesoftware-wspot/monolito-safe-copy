<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules\Rule;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;

class CustomFieldValidator
{

    use CustomFieldsAware;
    use TranslatorAware;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;
    }

    public function execute($fieldName, $value)
    {
        $errors = [];
        $field = $this->customFieldsService->getFieldByNameType($fieldName);
        $locale = $this->translator->getLocale();

        if ($field == null) {
            $errors[$fieldName][] = "Campo não pode ser inserido, pois não existe em seu formulário";
        } else {
            $validations = $field->getValidations() ? $field->getValidations() : [];
            foreach ($validations as $validation) {
                try {
                    $rule = $this->getRuleClass($validation['type']);
                    $result = $rule->validate($validation, $value, $locale);
                    if (!$result) {
                        $errors[$field->getIdentifier()][] = $this->translator->trans($validation['message']);
                    }
                } catch (\Exception $e) {
                    $errors['validation_rule'][]
                        = "Validação não existe no sistema, contate o suporte." . $e->getMessage();
                }
            }
        }
        return $errors;
    }

    /**
     * @param $validationType
     * @return Rule
     */
    private function getRuleClass($validationType)
    {
        $validationType = "core.api.validate.rule." . strtolower($validationType);
        return $this->container->get($validationType);
    }
}
