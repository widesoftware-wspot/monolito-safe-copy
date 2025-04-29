<?php
namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomFieldChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('isSaved', HiddenType::class, [
            'data' => "true",
            'mapped' => false,
        ])->add('isLabel', HiddenType::class, [
            'data' => "false",
            'mapped' => false,
        ])->add('pt_br', TextType::class, [
            'label' => false,
            'required' => true,
            'attr'     => [
                'class' => 'span14',
                'autocomplete' => 'off'
            ],
        ])
        ->add('es', TextType::class, [
            'label' => false,
            'required' => true,
            'attr'     => [
                'class' => 'span14',
                'autocomplete' => 'off'
            ],
        ])
        ->add('en', TextType::class, [
            'label' => false,
            'required' => true, 
            'attr'     => [
                'class' => 'span14',
                'autocomplete' => 'off'
            ],
        ]);

    }

    public function getBlockPrefix()
    {
        return 'wspot_custom_field_choice';
    }
}