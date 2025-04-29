<?php

namespace Wideti\AdminBundle\Form\Type\AccessPoints;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccessPointsFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'value',
                TextType::class,
                [
                    'label'     => false,
                    'required'  => false,
                    'attr'      => [
                        'placeholder' => 'Pesquise por Nome do Ponto de acesso, Mac Address ou Local',
                        'style' => 'width:380px;'
                    ]
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => [
                        'Todos' => 'all',
                        'Ativos' => 1,
                        'Inativos' => 0
                    ],
                    'placeholder' => false,
                    'attr'      => [
                        'style' => 'width:120px;'
                    ]
                ]
            )
            ->add(
                'filtrar',
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
        return 'access_points';
    }
}
