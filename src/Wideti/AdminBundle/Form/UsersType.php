<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Repository\UsersRepository;

class UsersType extends AbstractType
{
    private $userRepository;

    public function __construct(UsersRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
	     * @var Users $loggedUser
	     */
        $loggedUser = $options['attr']['logged_user'];
        $isRegularDomain = $options['attr']['is_wspot_domain'];

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
                        'class' => 'span12',
                        'style' => !($options['new']) ? 'pointer-events: none; background-color: #efefef;' : ''
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
                'role',
                EntityType::class,
                [
                    'label'       => 'Perfil',
                    'class'       => 'Wideti\DomainBundle\Entity\Roles',
                    'placeholder' => 'Selecione um perfil',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.role != :role_super_admin')
                            ->andWhere('r.role != :role_manager')
                            ->setParameter('role_super_admin', 'ROLE_SUPER_ADMIN')
                            ->setParameter('role_manager', 'ROLE_MANAGER')
                            ->orderBy('r.name', 'ASC');
                    },
                    'required'  => true,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'style' => !($options['new']) ? 'pointer-events: none; background-color: #efefef;' : ''
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'O perfil deve ser selecionado'])
                    ]
                ]
            )
        ;

        if(($loggedUser->getfinancialManager() || $loggedUser->getRole()->getRole() === Roles::ROLE_MANAGER) && $isRegularDomain){
            $builder
            ->add(
                'financial_manager',
                ChoiceType::class,
                [
                    'choices'       => [
                        'Não' => 0,
                        'Sim' => 1
                    ],
                    'required'      => true,
                    'label_attr'    => [
                        'class' => 'control-label'
                    ],
                    'label'         => 'Gestor Financeiro'
                ]
            );
        }

        $builder
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
        $resolver->setDefaults(
            [
                'data_class'  => 'Wideti\DomainBundle\Entity\Users',
                'new'         => false,
                'constraints' => new Callback([$this, 'checkAlreadyUsersExists'])
            ]
        );
    }
    public function checkAlreadyUsersExists($data, ExecutionContextInterface $context)
    {
        $client = $data->getClient();
        $exitsUsernameRegistered = $this->userRepository->verifyIfExitsAnotherUsernameRegistered($client, $data->getUsername(), $data->getId());

        if ($exitsUsernameRegistered) {
            return $context
                ->buildViolation(
                    'Esse e-mail já existe na sua base de dados'
                )
                ->atPath('username')
                ->addViolation();
        }
    }


    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_usuarios';
    }
}
