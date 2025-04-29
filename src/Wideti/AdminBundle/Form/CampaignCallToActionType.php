<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\CampaignCallToAction;

class CampaignCallToActionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $hasPreLoginImage = $options['attr']['hasPreLoginImage'];
        $hasPosLoginImage = $options['attr']['hasPosLoginImage'];

        $choices = [];

        $entity = isset($options['data'])
            ? $options['data']
            : null;

        if ($hasPreLoginImage) {
            $choices[1] = 'Pré-Login';
        }

        if ($hasPosLoginImage) {
            $choices[2] = 'Pós-Login';
        }

        $builder
            ->add(
                'campaignType',
                ChoiceType::class,
                [
                    'label' => 'Se aplica para',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'choices' => array_flip($choices),
                    'attr' => [
                        'class' => 'span2'
                    ]
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'label' => 'Status',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'choices' => [
                        'Ativo' => 1,
                        'Inativo' => 0
                    ],
                    'attr' => [
                        'class' => 'span2'
                    ]
                ]
            )
            ->add(
                'label',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Texto do Botão',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr'     => [
                        'class' => 'span9',
                    ]
                ]
            )
            ->add(
                'redirectUrl',
                TextType::class,
                [
                    'label'    => 'Redirecionar para',
                    'attr'     => [
                        'class' => 'span9',
                        'placeholder' => 'https://www.google.com'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'landscapeButtonWidth',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Largura do Botão (px)',
                    'attr'     => [
                        'class' => 'span9',
                    ],
                    'data' => empty($entity) || empty($entity->getLandscapeButtonWidth())
                        ? '100'
                        : $entity->getLandscapeButtonWidth(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'landscapeButtonSize',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Altura do Botão (px)',
                    'attr'     => [
                        'class' => 'span9',
                    ],
                    'data' => empty($entity) || empty($entity->getLandscapeButtonSize())
                        ? '50'
                        : $entity->getLandscapeButtonSize(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'landscapeButtonColor',
                TextType::class,
                [
                    'required' => true,
                    'block_name' => 'color',
                    'label'    => 'Cor do Botão',
                    'attr'     => [
                        'class' => 'span9',
                        'autocomplete' => 'off',
                        'style' => 'width: 85px;'
                    ],
                    'data' => empty($entity) || empty($entity->getLandscapeButtonColor())
                        ? "#ffffff"
                        : $entity->getLandscapeButtonColor(),
                    'label_attr' => [
                        'class' => 'control-label',
                    ]
                ]
            )
            ->add(
                'landscapeButtonLabelSize',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Tamanho da fonte (px)',
                    'attr'     => [
                        'class' => 'span9',
                    ],
                    'data' => empty($entity) || empty($entity->getLandscapeButtonLabelSize())
                        ?  "12"
                        : $entity->getLandscapeButtonLabelSize(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'landscapeButtonLabelColor',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Cor da Fonte',
                    'attr'     => [
                        'class' => 'span9',
                        'autocomplete' => 'off',
                        'style' => 'width: 85px;'
                    ],
                    'data' => empty($entity) || empty($entity->getLandscapeButtonLabelColor())
                        ? "#000000"
                        : $entity->getLandscapeButtonLabelColor(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'landscapeButtonHorizontalAlign',
                HiddenType::class,
                [
                    'label'    => 'Alinhamento Horizontal',
                    'attr'     => [
                        'class' => 'span9'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'landscapeButtonVerticalAlign',
                HiddenType::class,
                [
                    'label'    => 'Alinhamento Vertical',
                    'attr'     => [
                        'class' => 'span9'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'portraitButtonWidth',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Largura do Botão (px)',
                    'attr'     => [
                        'class' => 'span9',
                    ],
                    'data' => empty($entity) || empty($entity->getPortraitButtonWidth())
                        ? "100"
                        : $entity->getPortraitButtonWidth(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'portraitButtonSize',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Altura do Botão (px)',
                    'attr'     => [
                        'class' => 'span9',
                    ],
                    'data'      => empty($entity) || empty($entity->getPortraitButtonSize())
                        ? "50"
                        : $entity->getPortraitButtonSize(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'portraitButtonColor',
                TextType::class,
                [
                    'required' => true,
                    'block_name' => 'color',
                    'label'    => 'Cor do Botão',
                    'attr'     => [
                        'class' => 'span9',
                        'autocomplete' => 'off',
                        'style' => 'width: 85px;'
                    ],
                    'data'      => empty($entity) || empty($entity->getPortraitButtonColor())
                        ? "#ffffff"
                        : $entity->getPortraitButtonColor(),
                    'label_attr' => [
                        'class' => 'control-label',
                    ]
                ]
            )
            ->add(
                'portraitButtonLabelSize',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Tamanho da fonte (px)',
                    'attr'     => [
                        'class' => 'span9',
                    ],
                    'data'      => empty($entity) || empty($entity->getPortraitButtonLabelSize())
                        ? "12"
                        : $entity->getPortraitButtonLabelSize(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'portraitButtonLabelColor',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Cor da Fonte',
                    'attr'     => [
                        'class' => 'span9',
                        'autocomplete' => 'off',
                        'style' => 'width: 85px;'
                    ],
                    'data'      => empty($entity) || empty($entity->getPortraitButtonLabelColor())
                        ? "#000000"
                        : $entity->getPortraitButtonLabelColor(),
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'portraitButtonHorizontalAlign',
                HiddenType::class,
                [
                    'label'    => 'Alinhamento Horizontal',
                    'attr'     => [
                        'class' => 'span9'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'portraitButtonVerticalAlign',
                HiddenType::class,
                [
                    'label'    => 'Alinhamento Vertical',
                    'attr'     => [
                        'class' => 'span9'
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
                    'attr'   => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                    ],
                    'label' => 'Pré-visualizar'
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'      => CampaignCallToAction::class,
            'csrf_protection' => false
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_campaign_call_to_action';
    }
}
