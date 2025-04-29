<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class HubsoftIntegrationType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $groups = $this->mongo->getRepository('DomainBundle:Group\Group')->getGroupsToId();
        $builder
            ->add('enable_hubsoft_integration', CheckboxType::class, [
                'label' => 'Ativar integração',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['enable_hubsoft_integration'])
                    ? boolval($options['enable_hubsoft_integration']->getValue())
                    : false,
                'attr' => [
                    'class' => 'span8',
                ],
            ])
            ->add('enable_hubsoft_authentication', CheckboxType::class, [
                'label' => 'Autenticar cliente via Hubsoft',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['enable_hubsoft_authentication'])
                    ? boolval($options['enable_hubsoft_authentication']->getValue())
                    : false,
                'attr' => [
                    'class' => 'span8',
                ],
            ])
            ->add('enable_hubsoft_prospecting', CheckboxType::class, [
                'label' => 'Enviar dados de prospecto no cadastro dos visitantes',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['enable_hubsoft_prospecting'])
                    ? boolval($options['enable_hubsoft_prospecting']->getValue())
                    : false,
                'attr' => [
                    'class' => 'span8',
                ],
            ])
            ->add('hubsoft_client_id', TextType::class, [
                'label' => 'Client ID',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => $options['hubsoft_client_id']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('hubsoft_client_secret', TextType::class, [
                'label' => 'Client Secret',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => $options['hubsoft_client_secret']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('hubsoft_username', TextType::class, [
                'label' => 'Username',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => $options['hubsoft_username']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('hubsoft_auth_button', TextType::class, [
                'label' => 'Texto do botão de autenticação de cliente via Hubsoft',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $options['hubsoft_auth_button']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])->add('hubsoft_title_text', TextType::class, [
                'label' => 'Texto do título da tela de login da integração',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $options['hubsoft_title_text']->getValue(),
                'attr'     => [
                    'class' => 'span8',
                    'autocomplete' => 'off'
                ],
            ])->add('hubsoft_subtitle_text', TextType::class, [
                'label' => 'Texto do sub-título da tela de login da integração',
                'required' => false,
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $options['hubsoft_subtitle_text']->getValue(),
                'attr'     => [
                    'class' => 'span8',
                    'autocomplete' => 'off'
                ],
            ])->add('hubsoft_button_color', TextType::class, [
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'block_name' => 'color',
                'required' => false,
                'label'    => 'Cor do botão',
                'attr'     => array(
                    'class' => 'span6',
                    'autocomplete' => 'off',
                ),
                'label_attr' => array(
                    'class' => 'control-label',
                ),
                'data' => $options['hubsoft_button_color']->getValue(),
            ])->add('hubsoft_password', PasswordType::class, [
                'label' => 'Password',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => $options['hubsoft_password']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('hubsoft_host', TextType::class, [
                'label' => 'Host da API',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => $options['hubsoft_host']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('hubsoft_id_service', ChoiceType::class, [
                'label' => 'ID do Serviço',
                'choices'   => $options['service_ids'],
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $options['hubsoft_id_service']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off',
                        'disabled' => $options['service_ids'] ? false : true,
                    ],
            ])
            ->add('hubsoft_id_crm', TextType::class, [
                'label' => 'ID do CRM',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $options['hubsoft_id_crm']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('hubsoft_id_origin', ChoiceType::class, [
                'choices'   => $options['origin_ids'],
                'label' => 'ID da Origem do Cliente',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => $options['hubsoft_id_origin']->getValue(),
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off',
                        'disabled' => $options['origin_ids'] ? false : true,
                    ],
            ])
            ->add(
                'hubsoft_client_group',
                ChoiceType::class,
                [
                    'choices'   => array_flip($groups),
                    'label'     => 'Regras de acesso dos clientes autenticados',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => $options['hubsoft_client_group']->getValue(),
                    'required'  => true,
                    'attr' => [
                        'class' => 'span8'
                    ]
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => [
                        'disabled' => $options['hubsoft_credentials_ok'] ? false : true,
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
                'enable_hubsoft_integration' => null,
                'hubsoft_client_id' => null,
                'hubsoft_client_secret' => null,
                'hubsoft_username' => null,
                'hubsoft_password' => null,
                'hubsoft_host' => null,
                'hubsoft_client_group' => null,
                'hubsoft_id_service' => null,
                'hubsoft_id_origin' => null,
                'hubsoft_id_crm' => null,
                'enable_hubsoft_prospecting' => null,
                'enable_hubsoft_authentication' => null,
                'hubsoft_auth_button' => null,
                'hubsoft_credentials_ok' => false,
                'origin_ids' => [],
                'service_ids' => [],
                'hubsoft_title_text' => null,
                'hubsoft_subtitle_text' => null,
                'hubsoft_button_color' => null,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'wspot_hubsoft_integration';
    }
}