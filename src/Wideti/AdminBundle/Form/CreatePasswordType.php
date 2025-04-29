<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'password',
                RepeatedType::class,
                array(
                    'type' => PasswordType::class,
                    'invalid_message' => 'As senhas devem coincidir.',
                    'required' => true,
                    'first_options'  => array(
                        'label' => 'Senha',
                        'attr' => array(
                            'class' => 'span6',
                            'maxlength' => 30
                        ),
                        'label_attr' => array(
                            'class' => 'control-label'
                        ),
                    ),
                    'second_options' => array(
                        'label' => 'Repita a senha',
                        'attr' => array(
                            'class' => 'span6',
                            'maxlength' => 30
                        ),
                        'label_attr' => array(
                            'class' => 'control-label'
                        )
                    )
                )
            )
            ->add(
                'submit',
                SubmitType::class,
                array(
                    'label' => 'Cadastrar',
                    'attr'   => array(
                        'class' => 'btn btn-block btn-success',
                    )
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Wideti\DomainBundle\Entity\Users'
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_createpasswordtype';
    }
}
