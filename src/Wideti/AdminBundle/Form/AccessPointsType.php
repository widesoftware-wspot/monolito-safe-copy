<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\AdminBundle\Form\DeskbeeDeviceType;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Helpers\ValidationHelper;
use Wideti\DomainBundle\Service\Vendor\VendorAware;

class AccessPointsType extends AbstractType
{
    use VendorAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $clientId = $options['attr']['client'];
        $vendors = [];

        foreach ($this->vendor->getVendors() as $data) {
            if ($_SERVER["HTTP_HOST"] != "dev.wspot.com.br") {
                if (is_null($_SERVER["HTTP_X_SCOPE"]) && !$data->getIsHomologated()) {
                    continue;
                } elseif (array_key_exists("HTTP_X_SCOPE", $_SERVER) && !$data->getIsHomologated()){
                    if ($_SERVER["HTTP_X_SCOPE"] == "prod" || $_SERVER["HTTP_X_SCOPE"] == "batch-reports") continue;
                }
            }

            if (array_key_exists($data->getVendor(), vendor::VENDOR_MAP_BY_DISPLAY_NAME)) {
                $vendorLower = vendor::VENDOR_MAP_BY_DISPLAY_NAME[$data->getVendor()];
                $vendors[$vendorLower] = $data->getVendor();
            }
        }

        $builder
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices'   => [
                        'Ativo' => AccessPoints::ACTIVE,
                        'Inativo' => AccessPoints::INACTIVE
                    ],
                    'label' => 'Status',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10'
                    ]
                ]
            )
            ->add(
                'friendlyName',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Nome',
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'vendor',
                ChoiceType::class,
                [
                    'choices'   => array_flip($vendors),
                    'label'     => 'Fabricante',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10'
                    ],
                    'placeholder' => 'Selecione'
                ]
            )
            ->add(
                'identifier',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Mac Address do Equipamento',
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off',
                        'maxlength' => 30
                    ],
                    'label_attr' => [
                        'class' => 'control-label identifier'
                    ]
                ]
            )
            ->add(
                'local',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Local',
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'template',
                EntityType::class,
                [
                    'class' => 'DomainBundle:Template',
                    'label' => 'Template',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('t')
                            ->innerJoin('t.client', 'c', 'WITH', 'c.id = :client')
                            ->setParameter('client', $options['attr']['client'])
                            ->orderBy('t.name', 'ASC');
                    },
                    'required' => false,
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
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

        if ($options['deskbee_integration'] === true) {
            $builder
                ->add('deskbeeDevice', DeskbeeDeviceType::class, [
                    'required' => false,
                    'label'    => 'Catraca',
                    'attr'     => [
                        'class' => 'nested-form'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]);
        }
        if ($options['heatmap_module_active'] === true) {
            $builder
            ->add('shouldGetCoords', CheckboxType::class, [
                'label' => 'Salvar coordenadas',
                'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                'data' => false,
                'mapped' => false,
                ]);
        }
        if ($options['show_group'] === true) {
            $builder
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
                        'required' => true,
                        'attr'     => [
                            'class' => 'span10',
                            'autocomplete' => 'off'
                        ],
                        'label_attr' => [
                            'class' => 'control-label'
                        ]
                    ]
                );
        }

        if ($options['enable_disconnect_guest'] === true) {
            $builder
                ->add(
                    'publicIp',
                    TextType::class,
                    [
                        'required' => false,
                        'label'    => 'IP público',
                        'attr'     => [
                            'class' => 'span10',
                            'autocomplete' => 'off'
                        ],
                        'label_attr' => [
                            'class' => 'control-label'
                        ]
                    ]
                );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'            => 'Wideti\DomainBundle\Entity\AccessPoints',
                'new'                   => false,
                'show_group'            => false,
                'validation_groups'     => ['Default', 'AccessPoints'],
                'constraints'           => new Callback([$this, 'checkEmptyFields']),
                'allow_extra_fields'    => true,
                'enable_disconnect_guest' => false,
                'deskbee_integration'   => false,
                'heatmap_module_active'   => false
            ]
        );
    }

    public function checkEmptyFields($data, ExecutionContextInterface $context)
    {
        if (!$data->getTimezone()) {
            $context
                ->buildViolation('Selecione uma opção de timezone')
                ->atPath('wspot_access_point_timezone')
                ->addViolation();
        }

        if (!ValidationHelper::validateSpecialCharacterIdentify($data->getIdentifier())){
            return $context
                ->buildViolation(
                    'Caracteres especiais não são aceitos'
                )
                ->atPath('')
                ->addViolation();
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_access_point';
    }
}
