<?php

namespace Wideti\AdminBundle\Form\Type\Guest;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Document\CustomFields\Fields\FieldFactory;

class GuestFilterType extends AbstractType
{
    use MongoAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateFrom = new \DateTime("NOW -30 days");

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

        $groups = [];
        $groups['Todos'] = 'all';

        foreach ($this->mongo->getRepository('DomainBundle:Group\Group')->getGroupsToForm() as $key => $value) {
            $groups[$value] = $key;
        }

        $builder
            ->add(
                'filtro',
                ChoiceType::class,
                [
                    'label'         => 'Filtro : ',
                    'required'      => true,
                    'choices'       => $choices
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => [
                        'Todos' => 'all',
                        'Ativo' => Guests::STATUS_ACTIVE,
                        'Inativo' => Guests::STATUS_INACTIVE,
                        'Pendente de confirmação' => Guests::STATUS_PENDING_APPROVAL,
                        'Bloqueado por tempo' => Guests::STATUS_BLOCKED
                    ],
                    'label' => 'Status: ',
                    'attr'  => [
                        'class' => 'input-mini',
                        'style' => 'width: 150px;'
                    ]
                ]
            )
            ->add(
                'group',
                ChoiceType::class,
                [
                    'choices'   => $groups,
                    'label'     => 'Grupo: ',
                    'required'  => true,
                    'attr'      => [
                        'class' => 'input-mini',
                        'style' => 'width: 150px;'
                    ]
                ]
            )
            ->add(
                'dateFrom',
                DateType::class,
                [
                    'label'     => 'Cadastros de: ',
                    'required'  => false,
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'attr'      => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'dateTo',
                DateType::class,
                [
                    'label'    => 'até: ',
                    'widget'   => 'single_text',
                    'format'   => 'dd/MM/yyyy',
                    'required' => false,
                    'attr'     => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'Filtrar',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-default'
                    ]
                ]
            )
        ;

        $builder->setMethod('GET');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'visitantes';
    }
}
