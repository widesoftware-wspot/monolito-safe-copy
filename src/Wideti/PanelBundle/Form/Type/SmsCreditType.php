<?php

namespace Wideti\PanelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Wideti\DomainBundle\Entity\SmsGateway;

class SmsCreditType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'totalAvailable',
                IntegerType::class,
                [
                    'label' => 'Quantidade',
                    'label_attr' => [
                        'class' => 'control-label',
                        'style' => 'text-align: left'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required'  => true
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => ['class' => 'btn btn-icon btn-primary glyphicons circle_ok'],
                    'label' => 'Adicionar'
                ]
            )
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_panelbundle_smscredit';
    }
}
