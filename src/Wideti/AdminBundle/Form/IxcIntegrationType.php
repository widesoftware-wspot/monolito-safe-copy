<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

class IxcIntegrationType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $groups = $this->mongo->getRepository('DomainBundle:Group\Group')->getGroupsToId();
        $builder
            ->add('enable_Ixc_integration', CheckboxType::class, [
                'label' => 'Ativar integração',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['enable_Ixc_integration'])
                    ? boolval($options['enable_Ixc_integration']->getValue())
                    : false,
                'attr' => [
                    'class' => 'span8',
                ],
            ])
            ->add('enable_Ixc_authentication', CheckboxType::class, [
                'label' => 'Autenticar cliente via Ixc Soft',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['enable_Ixc_authentication'])
                    ? boolval($options['enable_Ixc_authentication']->getValue())
                    : false,
                'attr' => [
                    'class' => 'span8',
                ],
            ])
            ->add('enable_Ixc_prospecting', CheckboxType::class, [
                'label' => 'Enviar dados de Leads no cadastro dos visitantes',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['enable_Ixc_prospecting'])
                    ? boolval($options['enable_Ixc_prospecting']->getValue())
                    : false,
                'attr' => [
                    'class' => 'span8',
                ],
            ])
            ->add('Ixc_client_secret', TextType::class, [
                'label' => 'API Token',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => isset($options['Ixc_client_secret']) ? $options['Ixc_client_secret']->getValue() : null,
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add('Ixc_auth_button', TextType::class, [
                'label' => 'Texto do botão de autenticação de cliente via Ixc',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['Ixc_auth_button']) ? $options['Ixc_auth_button']->getValue() : null,
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])->add('Ixc_title_text', TextType::class, [
                'label' => 'Texto do título da tela de login da integração',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['Ixc_title_text']) ? $options['Ixc_title_text']->getValue() : null,
                'attr'     => [
                    'class' => 'span8',
                    'autocomplete' => 'off'
                ],
            ])->add('Ixc_subtitle_text', TextType::class, [
                'label' => 'Texto do sub-título da tela de login da integração',
                'required' => false,
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'data' => isset($options['Ixc_subtitle_text']) ? $options['Ixc_subtitle_text']->getValue() : null,
                'attr'     => [
                    'class' => 'span8',
                    'autocomplete' => 'off'
                ],
            ])->add('Ixc_button_color', TextType::class, [
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
                'data' => isset($options['Ixc_button_color']) ? $options['Ixc_button_color']->getValue() : null,
            ])
            ->add('Ixc_host', TextType::class, [
                'label' => 'Host da API',
                'label_attr' => [
                    'class' => 'control-label'
                ],
                'required' => true,
                'data' => isset($options['Ixc_host']) ? $options['Ixc_host']->getValue() : null,
                'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off'
                    ],
            ])
            ->add(
                'Ixc_client_group',
                ChoiceType::class,
                [
                    'choices'   => array_flip($groups),
                    'label'     => 'Regras de acesso dos clientes autenticados',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => isset($options['Ixc_client_group']) ? $options['Ixc_client_group']->getValue() : null,
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
                    // 'disabled' => !$options['Ixc_credentials_ok'], // Desabilita se credenciais não estiverem ok
                    'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                    'data-toggle' => $options['Ixc_credentials_ok'] ? null : 'tooltip', // Habilita o tooltip apenas se as credenciais não estiverem ok
                    'title' => $options['Ixc_credentials_ok'] ? null : 'Teste as credenciais antes de salvar' // Texto do tooltip apenas se as credenciais não estiverem ok
                ],
                    'label' => 'Salvar'
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'enable_Ixc_integration' => "1",
                // 'Ixc_client_id' => null,
                'Ixc_client_secret' => "123:0987654321",
                // 'Ixc_username' => null,
                // 'Ixc_password' => null,
                'Ixc_host' => 'https://demo.ixcsoft.com.br',
                'Ixc_client_group' => null,
                // 'Ixc_id_service' => null,
                // 'Ixc_id_origin' => null,
                // 'Ixc_id_crm' => null,
                'enable_Ixc_prospecting' => "1",
                'enable_Ixc_authentication' => "1",
                'Ixc_auth_button' => "Sou cliente  PROVEDOR X",
                'Ixc_credentials_ok' => false,
                // 'origin_ids' => [],
                // 'service_ids' => [],
                'Ixc_title_text' => "Login Ixc Soft",
                'Ixc_subtitle_text' => "Entre com seus dados de login Ixc Soft",
                'Ixc_button_color' => "#3754ed",
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'wspot_Ixc_integration';
    }
}