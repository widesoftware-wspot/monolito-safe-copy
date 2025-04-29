<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Form\GuestFieldType;

class GuestType extends AbstractType
{
    use EntityManagerAware;
    use MongoAware;
    use LoggerAware;
    use TranslatorAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->getCustomFields();
        $entity = $builder->getForm()->getData();
        $groups = $this->mongo->getRepository('DomainBundle:Group\Group')->getGroupsToForm();

        $guest = $options['data'];

        $registrationMacAddress = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'identifier' => $options['registrationMacAddress']
            ]);

        if ($entity->getStatus() == Guest::STATUS_ACTIVE || $entity->getStatus() == Guest::STATUS_INACTIVE) {
            $builder->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => [
                        'Ativo' => 1,
                        'Inativo' => 0
                    ],
                    'label' => 'Status',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ]
                ]
            );
        }

        $builder
            ->add(
                'emailValidate',
                HiddenType::class,
                [
                    'label'     => false,
                    'required'  => false,
                    'mapped'    => false,
                    'data'      => 'true'
                ]
            )
            ->add(
                'locale',
                ChoiceType::class,
                [
                    'choices' => [
                        'Português' => 'pt_br',
                        'Inglês' => 'en',
                        'Espanhol' => 'es',
                    ],
                    'label' => 'Idioma',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ]
                ]
            );

        if ($options['authorizeEmail']) {
            $builder
                ->add(
                    'authorizeEmail',
                    ChoiceType::class,
                    [
                        'choices' => [
                            'Sim' => 1,
                            'Não' => 0
                        ],
                        'label' => 'Autoriza opt-in?',
                        'label_attr' => [
                            'class' => 'control-label'
                        ],
                        'required'  => true,
                        'attr' => [
                            'class' => 'span12'
                        ],
                        'data' => $guest->getAuthorizeEmail() == 1 ? 1 : 0
                    ]
                );
        }

        if ($entity->getId() == null) {
            $builder->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'As senhas devem coincidir.',
                    'required' => true,
                    'first_options'  => [
                        'label' => 'Senha',
                        'attr' => [
                            'class' => 'span4',
                            'maxlength' => 30,
                            'data-msg-maxlength' => $this->translator->trans(
                                'wspot.signup_page.field_password_max_characters_required'
                            ),
                            'data-rule-required' => 'true',
                            'data-msg-required' => $this->translator->trans('wspot.signup_page.field_required'),
                            'data-rule-minlength' => 6,
                            'data-msg-minlength' => $this->translator->trans(
                                'wspot.signup_page.field_password_min_characters_required'
                            )
                        ],
                        'label_attr' => [
                            'class' => 'control-label'
                        ]
                    ],
                    'second_options' => [
                        'label' => 'Repita a senha',
                        'attr' => [
                            'class' => 'span4',
                            'maxlength' => 30,
                            'data-msg-maxlength' => $this->translator->trans(
                                'wspot.signup_page.field_password_max_characters_required'
                            ),
                            'data-rule-required' => 'true',
                            'data-msg-required' => $this->translator->trans('wspot.signup_page.field_required'),
                            'data-rule-minlength' => 6,
                            'data-msg-minlength' => $this->translator->trans(
                                'wspot.signup_page.field_password_min_characters_required'
                            ),
                            'data-rule-equalTo' => '#wspot_guest_password_first',
                            'data-msg-equalTo' => $this->translator->trans(
                                'wspot.signup_page.field_password_equalTo_required'
                            )
                        ],
                        'label_attr' => [
                            'class' => 'control-label'
                        ]
                    ]
                ]
            );
        }

        $builder->add(
            'group',
            ChoiceType::class,
            [
                'choices'   => array_flip($groups),
                'label'     => 'Grupo',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required'  => true,
                'attr' => [
                    'class' => 'span12'
                ]
            ]
        )
        ->add(
            'registrationMacAddress',
            EntityType::class,
            [
                'class' => 'DomainBundle:AccessPoints',
                'label' => 'Ponto de acesso',
                'disabled' => 'Selecione',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('a')
                        ->innerJoin('a.client', 'c', 'WITH', 'c.id = :client')
                        ->where('a.status = 1')
                        ->setParameter('client', $options['client'])
                        ->orderBy('a.friendlyName', 'ASC');
                },
                'required' => true,
                'attr'     => [
                    'class' => 'span12',
                    'autocomplete' => 'off'
                ],
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $registrationMacAddress
            ]
        );

        $builder->add(
            'properties',
            GuestFieldType::class,
            [
                'fields'        => $fields,
                'label'         => false,
                'property_path' => 'properties',
                'guest'         => $entity
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'attr' => [
                    'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                ],
                'label' => 'Salvar'
            ]
        );
    }

    /**
     * @return mixed
     */
    private function getCustomFields()
    {
        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields();

        $fields = $fields->toArray();

        usort($fields, function ($field) {
            return ($field->getIdentifier() == 'email') ? -1 : 1;
        });

        return $fields;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'             => "Wideti\\DomainBundle\\Document\\Guest\\Guest",
            'authorizeEmail'         => false,
            'client'                 => null,
            'registrationMacAddress' => null
        ]);
    }

    public function getBlockPrefix()
    {
        return 'wspot_guest';
    }
}
