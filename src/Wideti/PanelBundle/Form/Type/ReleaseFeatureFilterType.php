<?php

namespace Wideti\PanelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ReleaseFeatureFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'option',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices' => [
                        'Domínio' => 'domains',
                        'Funcionalidade' => 'feature',
                    ],
                    'placeholder' => 'Selecione',
                    'label' => 'Filtrar por',
                    'attr' => [
                        'class' => 'span12',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )->add(
                'value',
                TextType::class,
                [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'style' => 'width:260px;'
                    ]
                ]
            )->add(
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
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return "wspot_release_feature_filter";
    }
}