<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SsoIntegrationType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $groups = $this->mongo->getRepository('DomainBundle:Group\Group')->getGroupsToId();

        if ($options['attr']['actionForm'] !== 'update') {
            $builder
                ->add('ssoType', ChoiceType::class, array(
                    'choices'  => $options['ssoTypes'],
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true,
                    'data' => 'default',
                    'label' => 'Selecione o tipo de integração desejada:',
                        'label_attr' => [
                            'class' => 'control-label'
                        ],
                ));
        }
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome da conexão',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('label', TextType::class, [
                'label' => 'Texto no botão do captive (PT {{ image_placeholder }})',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                
            ])
            ->add('labelEn', TextType::class, [
                'label' => 'Texto no botão do captive (EN {{ image_placeholder }})',
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label',
                    ]
            ])
            ->add('labelEs', TextType::class, [
                'label' => 'Texto no botão do captive (ES {{ image_placeholder }})',
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL Base',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('authorizeUrl', UrlType::class, [
                'label' => 'URL de Autorização (Authorize)',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('tokenUrl', UrlType::class, [
                'label' => 'URL de Token (access_token)',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('clientId', TextType::class, [
                'label' => 'Application/Client ID',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('clientSecret', TextType::class, [
                'label' => 'Client Secret',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('fieldLogin', TextType::class, [
                'label' => 'Campo identificador',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('resource', TextType::class, [
                'label' => 'Resource',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('scope', TextType::class, [
                'label' => 'Escopo',
                'required' => true,
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
            ])
            ->add('tokenType', ChoiceType::class, [
                'choices' => [
                    'Sim' => 'id_token',
                    'Não' => 'access_token',
                ],
                'expanded' => true,
                'label' => 'Usa protocolo Open ID Connect(OIDC)?',
                'multiple' => false,
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'attr' => [
                    'class' => 'span8 select'
                ],
            ])
            ->add(
                'customizeGuestGroup',
                ChoiceType::class,
                [
                    'choices'   => array_flip($groups),
                    'label'     => 'Regra de Acesso',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span8'
                    ]
                ]
            )
            ->add(
                'group',
                EntityType::class,
                [
                    'class' => 'DomainBundle:AccessPointsGroups',
                    'label' => 'Grupo',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('g')
                            ->innerJoin('g.client', 'c', 'WITH', 'c.id = :client')
                            ->setParameter('client', $options['attr']['client'])
                            ->orderBy('g.id', 'ASC');
                    },
                    'required' => false,
                    'placeholder' => 'Todos os pontos de acesso',
                    'empty_data' => null,
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add('requestMissingFields', ChoiceType::class, [
                'choices' => [
                    'Sim' => true,
                    'Não' => false,
                ],
                'expanded' => true,
                'label' => 'Completar cadastro após login?',
                'multiple' => false,
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'attr' => [
                    'class' => 'span8 select'
                ],
            ])
            ->add(
                'domain',
                HiddenType::class,
                ['data' => $options['attr']['clientDomain']]
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'ssoTypes' => array()
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'wspot_sso_integration';
    }
}