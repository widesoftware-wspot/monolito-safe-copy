<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PerfilPasswordType extends AbstractType
{
    /**
    * @param FormBuilderInterface $builder
    * @param array $options
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => '',
                    'options' => [
                        'attr' => [
                            'maxlength' => 30,
                            'class' => 'span12'
                        ]
                    ],
                    'required' => false,
                    'first_options'  => [
                        'label' => 'Nova senha:',
                        'label_attr' => [
                            'class' => 'control-label'
                        ]
                    ],
                    'second_options' => [
                        'label' => 'Confirmar a nova senha:',
                        'label_attr' => [
                            'class' => 'control-label'
                        ]
                    ]
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Salvar',
                    'attr'   => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                    ]
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Wideti\DomainBundle\Entity\Users'
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'user_profile';
    }
}
