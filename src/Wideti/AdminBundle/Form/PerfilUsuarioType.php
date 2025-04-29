<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PerfilUsuarioType extends AbstractType
{
    /**
    * @param FormBuilderInterface $builder
    * @param array $options
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                EmailType::class,
                [
                    'label' => 'Usuário (E-mail)',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'disabled' => true,
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ]
                ]
            )
            ->add(
                'nome',
                TextType::class,
                [
                    'label' => 'Nome ',
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'receive_report_mail',
                ChoiceType::class,
                [
                    'choices' => [
                        'Sim' => 1,
                        'Não' => 0
                    ],
                    'required'  => true,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'label' => 'Receber Resumo Semanal via e-mail'
                ]
            )
            ->add(
                'report_mail_language',
                ChoiceType::class,
                [
                    'choices' => [
                        'Português' => 0,
                        'Inglês' => 1
                    ],
                    'required'  => true,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'label' => 'Idioma do Resumo Semanal'
                ]
            )
            ->add(
                'two_factor_authentication_enabled',
                ChoiceType::class,
                [
                    'choices' => [
                        'Inativo' => 0,
                        'Ativo' => 1
                    ],
                    'required'  => true,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'label' => 'Validação em duas etapas'
                ]
            )
            ->add(
                'two_factor_authentication_code',
                TextType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'label_attr' => [
                        'class' => 'control-label',
                        'id'    => 'user_profile_two_factor_authentication_code_label'
                    ],
                    'label' => 'Código Google Authenticator',
                ]
            )
            ->add(
                'two_factor_authentication_secret',
                HiddenType::class
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
