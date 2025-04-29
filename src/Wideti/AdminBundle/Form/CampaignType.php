<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\Campaign;

class CampaignType extends AbstractType
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em       = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $submitLabel    = "Avançar";
        $editCampaign   = $this->getEditableCampaignValues($options);
        $editBgColor    = null;

        if (isset($editCampaign)) {
            $editBgColor = $editCampaign->getBgColor();
        }

        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Título da Campanha',
                    'attr'     => [
                        'class' => 'span10'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'startDate',
                DateType::class,
                [
                    'required'  => true,
                    'label'     => 'Início',
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
                'endDate',
                DateType::class,
                [
                    'required'  => true,
                    'label'     => 'Fim',
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
                'campaignHours',
                CollectionType::class,
                [
                    'required'     => true,
                    'label'        => false,
                    'entry_type'   => CampaignHoursType::class,
                    'allow_add'    => true,
                    'by_reference' => false,
                    'allow_delete' => true,
                    'constraints'  => new NotBlank()
                ]
            )
            ->add(
                'ssid',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Nome da Rede (SSID)',
                    'attr'     => [
                        'class' => 'span10'
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
                    'placeholder' => 'Herdar do ponto de acesso / Grupo',
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
                'apsAndGroups',
                HiddenType::class,
                [
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'redirectUrl',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'URL de Redirecionamento',
                    'attr'     => [
                        'class' => 'span10'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'constraints' => array(new Url())
                ]
            )
            ->add(
                'bgColor',
                TextType::class,
                [
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor do fundo',
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off',
                        'value' => $editBgColor ? $editBgColor : '#000000',
                    ],
                    'label_attr' => [
                        'class' => 'control-label',
                    ]
                ]
            )
        ;

        if ($builder->getData()->getId() !== null) {
            $builder->add(
                'status',
                ChoiceType::class,
                [
                    'choices'   => [
                        'Inativa' => Campaign::STATUS_INACTIVE,
                        'Ativa' => Campaign::STATUS_ACTIVE,
                        'Rascunho' => Campaign::STATUS_DRAFT,
                        'Expirada' => Campaign::STATUS_EXPIRED
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
            );
        }

        if ($builder->getData()->getId() !== null) {
            $builder
                ->add(
                    'submitAndExit',
                    SubmitType::class,
                    [
                        'attr'   => [
                            'class' => 'btn btn-icon btn-primary glyphicons chevron-left',
                        ],
                        'label' => 'Salvar e voltar para listagem'
                    ]
                );
        }

        $builder
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr'   => [
                        'class' => 'btn btn-icon btn-primary glyphicons chevron-right',
                    ],
                    'label' => $submitLabel
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_campaign';
    }

    /**
     * @param array $options
     * @return Campaign | null
     */
    private function getEditableCampaignValues(array $options)
    {
        if (isset($options['data']) && $options['data'] instanceof Campaign) {
            return $options['data'];
        }
        return null;
    }
}
