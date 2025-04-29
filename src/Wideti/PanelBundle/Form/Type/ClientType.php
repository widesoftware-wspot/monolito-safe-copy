<?php

namespace Wideti\PanelBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Wideti\DomainBundle\Entity\Client;

class ClientType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity = $builder->getForm()->getData();

        if ($entity->getId() == null) {
            $builder
                ->add(
                    'domain',
                    TextType::class,
                    [
                        'label' => 'Domínio',
                        'label_attr' => [
                            'class' => 'control-label',
                            'id' => 'label_document'
                        ],
                        'attr' => [
                            'class' => 'span12'
                        ],
                        'required'  => true
                    ]
                )
            ;
        }

        $builder
            ->add(
                'erp_id',
                IntegerType::class,
                [
                    'label' => 'ID ERP (Superlógica)',
                    'label_attr' => [
                        'class' => 'control-label',
                        'id' => 'label_document'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required'  => true
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => [
                        Client::TYPE_SIMPLE     => 'Simples',
                        Client::TYPE_PROVIDER   => 'Provedor'
                    ],
                    'label' => 'Tipo de cliente',
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
                'company',
                TextType::class,
                [
                    'label' => 'Razão Social',
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
                'document',
                TextType::class,
                [
                    'label' => 'CPF/CNPJ',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'zipCode',
                TextType::class,
                [
                    'label' => 'CEP',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'address',
                TextType::class,
                [
                    'label' => 'Endereço',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'addressNumber',
                TextType::class,
                [
                    'label' => 'Número',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'addressComplement',
                TextType::class,
                [
                    'label' => 'Complemento',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'district',
                TextType::class,
                [
                    'label' => 'Bairro',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'city',
                TextType::class,
                [
                    'label' => 'Cidade',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'state',
                TextType::class,
                [
                    'label' => 'Estado',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        "max_length" => 2,
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'smsCost',
                TextType::class,
                [
                    'label' => 'Valor do SMS',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => false
                ]
            )
            ->add(
                'contractedAccessPoints',
                TextType::class,
                [
                    'label' => 'Quantidade de APs Contratadas',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'required' => true
                ]
            )
            ->add(
                'closingDate',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'Dia de Fechamento',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                    'constraints' => [
                        new Range([
                            "min" => 1,
                            "max" => 31,
                            "minMessage" => "O dia deve ser maior do que 0",
                            "maxMessage" => "O dia deve ser menor do que 31"
                        ])
                    ]
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'label' => 'Status',
                    'choices'   => [
                        'Ativo' => '1',
                        'Inativo' => '0',
                        'PoC' => '2',
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ],
                ]
            )
            ->add(
                'apCheck',
                ChoiceType::class,
                [
                    'label' => 'Verificar Configuração de AP',
                    'choices'   => [
                        'Sim' => '1',
                        'Não' => '0',
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ],
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => ['class' => 'btn btn-icon btn-primary glyphicons circle_ok'],
                    'label' => 'Enviar'
                ]
            )
            ->add(
                'module',
                EntityType::class,
                [
                    'class' => 'DomainBundle:Module',
                    'label' => 'Módulos',
                    'placeholder' => 'Selecione',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('m')
                            ->orderBy('m.name');
                    },
                    'required' => false,
                    'multiple' => true,
                    'attr'     => [
                        'class'         => 'span12',
                        'autocomplete'  => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'pocEndDate',
                DateType::class,
                [
                    'required'  => false,
                    'label'     => 'Fim da PoC',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'attr'      => [
                        'class' => 'span12',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
	        ->add(
		        'plan',
		        EntityType::class,
		        [
			        'class' => 'DomainBundle:Plan',
			        'label' => 'Plano do cliente',
			        'query_builder' => function (EntityRepository $er) use ($options){
				        return $er->createQueryBuilder('p')
					        ->orderBy('p.plan', 'DESC');
			        },
			        'multiple' => false,
			        'required' => true,
			        'attr'     => [
				        'class' => 'span12',
				        'autocomplete' => 'off'
			        ],
			        'label_attr' => [
				        'class' => 'control-label'
			        ]
		        ]
	        )
	        ->add(
		        'segment',
		        EntityType::class,
		        [
			        'class' => 'DomainBundle:Segment',
			        'label' => 'Segmento do cliente',
			        'query_builder' => function (EntityRepository $er) use ($options){
				        return $er->createQueryBuilder('s')
					        ->orderBy('s.id', 'ASC');
			        },
			        'multiple' => false,
			        'required' => true,
			        'attr'     => [
				        'class' => 'span12',
				        'autocomplete' => 'off'
			        ],
			        'label_attr' => [
				        'class' => 'control-label'
			        ],
			        'placeholder' => 'Selecione...'
		        ]
	        )
            ->add(
                'enableMacAuthentication',
                ChoiceType::class,
                [
                    'label' => 'Autenticação por MAC?',
                    'choices'   => [
                        'Não' => '0',
                        'Sim' => '1',
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ],
                ]
            )
            ->add(
                'authenticationType',
                ChoiceType::class,
                [
                    'label' => 'Tipo de captive',
                    'mapped' => false,
                    'choices'   => [
                        'Com senha' => 'enable_password_authentication',
                        'Sem senha' => 'disable_password_authentication',
                        'Sem campos' => 'no_register_fields',
                    ],
                    'data' => $entity->getAuthenticationType(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'choice_attr' => function ($choice) use ($options) {
                        $editPasswordAuthentication = $options['editPasswordAuthentication'];
                        $canEnableNoRegisterFields = $options['canEnableNoRegisterFields'];
                        $canDisableNoRegisterFields = $options['canDisableNoRegisterFields'];

                        if ($choice === 'enable_password_authentication' && !$editPasswordAuthentication) {
                            return ['disabled' => 'disabled'];
                        }

                        if ($choice === 'no_register_fields' && !$canEnableNoRegisterFields) {
                            return ['disabled' => 'disabled'];
                        }
                        if ($choice !== 'no_register_fields' && !$canDisableNoRegisterFields) {
                            return ['disabled' => 'disabled'];
                        }
                        return [];
                    },
                    'required'  => true,
                    'attr' => [
                        'class' => 'span12'
                    ],
                ]
            )
            ->add(
                'allowFakeData',
                CheckboxType::class,
                [
                    'label' => 'Permitir a geração de dados falsos?',
                    'required' => false,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ],
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Wideti\DomainBundle\Entity\Client',
            'editPasswordAuthentication'           => true,
            'canEnableNoRegisterFields'            => true,
            'canDisableNoRegisterFields'           => true,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_panelbundle_client';
    }
}
