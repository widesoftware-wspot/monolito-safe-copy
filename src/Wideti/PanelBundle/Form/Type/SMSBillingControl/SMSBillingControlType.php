<?php

namespace Wideti\PanelBundle\Form\Type\SMSBillingControl;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SMSBillingControlType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'date_from',
                DateType::class,
                [
                    'disabled' => true,
                    'label'     => 'Período de',
                    'required'  => false,
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => new \DateTime("NOW"),
                    'attr'      => [
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'date_to',
                DateType::class,
                [
                    'disabled' => true,
                    'label'     => 'até',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'required'  => false,
                    'data'      => new \DateTime("NOW"),
                    'attr'      => [
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices'  => [
                        'Pendente' => '0',
                        'Enviado' => '1',
                    ],
                    'attr' => [
                        'style'    => 'width: 100px;'
                    ],
                    'placeholder' => 'Selecione'
                ]
            )
            ->add(
                'filtro',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices' => [
                        'Razão Social' => 'client',
                        'ERP ID' => 'erpId',
                        'Domínio' => 'domain',
                    ],
                    'placeholder' => 'Escolha uma opção'
                ]
            )
            ->add(
                'value',
                TextType::class,
                [
                    'required' => false,
                    'label' => ' ',
                    'attr'  => [
                        'class'    => 'input-mini',
                        'style'    => 'width: 70px;'
                    ],
                ]
            )
            ->add(
                'Filtrar',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-default'
                    ],
                ]
            );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }
}