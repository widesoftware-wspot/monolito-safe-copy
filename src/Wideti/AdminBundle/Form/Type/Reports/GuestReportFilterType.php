<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;

class GuestReportFilterType extends AbstractType
{
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    public function __construct(CustomFieldsService $customFieldsService)
    {
        $this->customFieldsService = $customFieldsService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $guestFilter = null;
        $dateFrom    = new \DateTime("NOW -30 days");

        if ($options['attr']['unique'] == 1) {
            $guestFilter = 'unique';
        } elseif ($options['attr']['recurring'] == 1) {
            $guestFilter = 'recurring';
        }

        $builder
            ->add(
                'recurrence',
                ChoiceType::class,
                [
                    'required'  => false,
                    'data'      => $guestFilter,
                    'choices'   => [
                        'Recorrentes' => 'recurring',
                        'Únicos' => 'unique',
                    ],
                    'label'       => 'Visitantes',
                    'placeholder' => 'Todos',
                    'attr' => [
                        'style' => 'width:100%'
                    ]
                ]
            )
            ->add(
                'range_by',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices'  => [
                        'Visitas entre' => 'lastAccess',
                        'Cadastros entre' => 'created',
                    ],
                    'label'       => 'Filtrar por ',
                    'placeholder' => false,
                    'attr' => [
                        'style' => 'width:100%'
                    ]
                ]
            )
            ->add(
                'date_from',
                DateType::class,
                [
                    'label'     => false,
                    'required'  => false,
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => $dateFrom,
                    'attr'      => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'date_to',
                DateType::class,
                [
                    'label'    => 'até',
                    'widget'   => 'single_text',
                    'format'   => 'dd/MM/yyyy',
                    'required' => false,
                    'data'     => new \DateTime("NOW"),
                    'attr'     => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'filtrar',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-default'
                    ]
                ]
            );

        $builder->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['csrf_protection' => false]);
    }

    public function getBlockPrefix()
    {
        return 'guestReportsFilter';
    }
}
