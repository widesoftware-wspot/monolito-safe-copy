<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WhiteLabelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'companyName',
                TextType::class,
                [
                    'required' => true,
                    'label'    => false,
                    'attr'     => [
                        'class' => 'span12',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'panelColor',
                TextType::class,
                [
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off',
                        'style' => 'width: 165px; margin-right: 30px;'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'fileLogotipo',
                FileType::class,
                [
	                'data_class'    => null,
                    'required'      => false,
                    'label'         => false
                ]
            )
            ->add('logotipo', HiddenType::class)
            ->add(
                'signature',
                TextareaType::class,
                [
                    'required' => true,
                    'label'    => false,
                    'attr'     => [
                        'class' => 'wysihtml5 span12',
                        'rows'  => 1
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                    ],
                    'label' => 'Salvar'
                ]
            )
        ;
    }

    public function getBlockPrefix()
    {
        return 'wspot_white_label';
    }
}
