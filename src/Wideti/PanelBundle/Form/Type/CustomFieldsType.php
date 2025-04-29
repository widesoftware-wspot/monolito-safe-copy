<?php

namespace Wideti\PanelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomFieldsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields     = $options['data'];
        $allFields  = $options['allFields'];

        $builder
            ->add(
                'fields',
                ChoiceType::class,
                array(
                    'label'         => 'Campos',
                    'multiple'      => true,
                    'required'      => false,
                    'attr' => array(
                        'multiple'  => 'multiple'
                    ),
                    'data'      => array_keys($fields),
                    'choices'   => $allFields
                )
            )
            ->add(
                'submit',
                SubmitType::class,
                array(
                    'attr' => array('class' => 'btn btn-icon btn-primary glyphicons circle_ok'),
                    'label' => 'Salvar'
                )
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allFields' => true
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_panel_custom_fields';
    }
}
