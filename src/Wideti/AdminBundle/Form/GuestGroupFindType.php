<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 30/03/17
 * Time: 16:36
 */

namespace Wideti\AdminBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Document\CustomFields\Fields\FieldFactory;

class GuestGroupFindType extends AbstractType
{
    use MongoAware;
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];

        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields();

        foreach ($fields as $field) {
            $locale = 'pt_br';
            $choices[$field->getNames()[$locale]] = 'properties.' . $field->getIdentifier();
            $typeName = $field->getType();
            if ($typeName != "choice" && $typeName != "multiple_choice") {
                $typeName = "text";
            }
            $fieldFactory  = new FieldFactory($typeName);
            $type          = $fieldFactory->getType();
            $options                = $type->getOptions();
            $options['label']       = ' ';
            if ($type->getType() == ChoiceType::class) {
                $fieldChoices = [];
                $fieldChoices["Todos"] = "all";
                $fieldChoices["Não informado"] = "none";
                $fieldChoices['─────────────────'] = 'separator';
                foreach ($field->getChoices()[$locale] as $key => $choice) {
                    if ($choice != "") {
                        $fieldChoices[$choice] = $key;
                    }
                }
                $options['choices'] = $fieldChoices;
                $options['multiple']    = false;
                $options['placeholder']  = false;
            }
            $options['attr'] = [
                'class' => 'value-field value-' . $field->getIdentifier(),
                'style' => 'width: 250px;'
            ];
            $options['required'] = false;
            $options['constraints'] = [];
            $builder->add('value_' . $field->getIdentifier(), $type->getType(), $options);
            
            if ($type->getModelTransformer() !== null) {
                $builder->get('value_' . $field->getIdentifier())
                ->addModelTransformer($type->getModelTransformer())
                ;
            }
        }
        $choices['Todos'] = 'all';
        $builder
            ->add(
                'filter',
                ChoiceType::class,
                [
                    'label'         => 'Filtro : ',
                    'required'      => true,
                    'choices'       => $choices
                ]
            )->add(
            'Filtrar',
            SubmitType::class,
            [
                'attr' => [
                    'class' => 'btn btn-default'
                ]
            ]
        );

        $builder->setMethod('GET');
    }


    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return "wspot_guest_group_find";
    }
}
