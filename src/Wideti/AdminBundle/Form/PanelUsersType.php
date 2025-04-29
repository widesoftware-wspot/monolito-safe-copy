<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PanelUsersType extends AbstractType
{

    public function __construct()
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                EmailType::class,
                [
                    'label'      => 'E-mail',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'constraints' => [
                        new Email(['message' => 'E-mail digitado é inválido']),
                        new NotBlank(['message' => 'O e-mail deve ser preenchido'])
                    ]
                ]
            )
            ->add(
                'nome',
                TextType::class,
                [
                    'label' => 'Nome Completo',
                    'attr'  => [
                        'class' => 'span12'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'O nome do usuário é obrigatório'])
                    ]
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => [
                        'Ativo' => 1,
                        'Inativo' => 0
                    ],
                    'required'      => true,
                    'label_attr'    => [
                        'class' => 'control-label'
                    ],
                    'label'       => 'Status',
                    'constraints' => [
                        new NotBlank(['message' => 'O status deve ser selecionado'])
                    ]
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                    'required'   => true,
                    'label_attr' => [ 'class' => 'control-label' ],
                    'label'      => 'Senha',
                    'always_empty' => false
                ]
            )
            ->add(
                'role',
                EntityType::class,
                [
                    'label'         => 'Perfil',
                    'class'         => 'Wideti\DomainBundle\Entity\Roles',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.role = :role')
                            ->setParameter('role', 'ROLE_MANAGER');
                    },
                    'required'  => true,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'O perfil deve ser selecionado'])
                    ]
                ]
            )
            ->add(
                'financial_manager',
                HiddenType::class,
                [ 'data' => 0 ]
            )
            ->add(
                'receive_report_mail',
                HiddenType::class,
                [ 'data' => 0 ]
            )
            ->add(
                'report_mail_language',
                HiddenType::class,
                [ 'data' => 0 ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Salvar',
                    'attr'  => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                    ]
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'Wideti\DomainBundle\Entity\Users']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_panel_usuarios';
    }


}
