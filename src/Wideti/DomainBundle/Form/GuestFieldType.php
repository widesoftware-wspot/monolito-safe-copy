<?php

namespace Wideti\DomainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Document\CustomFields\Fields\FieldFactory;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;

class GuestFieldType extends AbstractType
{
    use TranslatorAware;

    /**
     * @var Field
     */
    protected $field;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Guest $guest */
        $guest  = $options["guest"];
        $locale = 'pt_br';

        if ($guest) {
            $locale = ($guest->getLocale()) ?: 'pt_br';
        }
    
    
        foreach ($options['fields'] as $field) {
            $this->field   = $field;
            $fieldFactory  = new FieldFactory($this->field->getType());
            $type          = $fieldFactory->getType();
    
    
            $options                = $type->getOptions();
            $options['label']       = $this->field->getNameByLocale($locale);
            $options['constraints'] = $type->getValidators();
    
            if ($type->getType() === ChoiceType::class) {
                $options['choices'] = $this->field->getChoices()[$locale];
            }

            $options = $this->addValidationRules($options, $locale);    
            $builder->add($this->field->getIdentifier(), $type->getType(), $options);

            if ($type->getModelTransformer() !== null) {
                $builder->get($this->field->getIdentifier())
                    ->addModelTransformer($type->getModelTransformer())
                ;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'fields' => [],
            'guest'  => null,
            'property_path' => 'properties'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'wspot_guest_field';
    }

    private function addValidationRules(array $options, $locale)
    {
        $options['required'] = false;
        $mask = $this->field->getMask();
        $options['attr']['data-field-mask'] = !empty($mask) ? $mask[$locale] : "";
        $rules = $this->field->getValidations();

        if ($rules) {
            foreach ($rules as $rule) {
                if (in_array($locale, $rule['locale'])) {
                    $attrRule = 'data-rule-' . $rule['type'];
                    $attrMessage = 'data-msg-' . $rule['type'];
                    $options['attr'][$attrRule] = \GuzzleHttp\json_encode($rule['value']);
                    $options['attr'][$attrMessage] = $this->translator->trans($rule['message']);
                }
            }
        }
        return $options;
    }
}
