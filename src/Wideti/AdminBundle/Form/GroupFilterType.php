<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label'  => 'Nome da regra',
                'required' => false,
                'attr'   => [
                    'class' => 'input-mini',
                    'style' => 'width: 250px;',
                ],
            ]
        );

        $builder->add(
            'Filtrar',
            SubmitType::class,
            [
                'attr' => [
                    'class' => 'btn btn-default',
                ],
            ]
        );

        $builder->setMethod('GET');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
            'csrf_protection' => false,
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_group_form_filter';
    }
}
