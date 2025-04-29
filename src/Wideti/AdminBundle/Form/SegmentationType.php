<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SegmentationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateFrom = (isset($options['data']->startDate))
            ? $options['data']->startDate
            : new \DateTime('NOW -30 days');

        $dateTo = (isset($options['data']->endDate))
            ? $options['data']->endDate
            : new \DateTime('NOW');

        $filterValue = (isset($options['data']->filterValue)) ? $options['data']->filterValue : null;

        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Título',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span12'
                    ]
                ]
            )
            ->add(
                'filter',
                ChoiceType::class,
                [
                    'data' => $filterValue,
                    'placeholder' => 'Selecione...',
                    'choices' => [
                        "Cadastros" => "registrations",
                        "Visitas" => "visits",
                        "Visitantes Únicos" => "uniqueGuests",
                        "Visitantes Recorrentes" => "recurringGuests",
                        "Aniversariantes" => "birthdays",
                    ],
                    'label' => 'Filtrar por',
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
                'startDate',
                DateType::class,
                [
                    'attr' => array(
                        'readonly' => true,
                    ),
                    'required'  => true,
                    'label'     => 'De',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => $dateFrom,
                    'attr'      => [
                        'class' => 'span6',
                        'autocomplete' => 'off',
                        'style' => 'width: 100%'
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
                    'attr' => array(
                        'readonly' => true,
                    ),
                    'required'  => true,
                    'label'     => 'Até',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => $dateTo,
                    'attr'      => [
                        'class' => 'span6',
                        'autocomplete' => 'off',
                        'style' => 'width: 100%'
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
                    'attr' => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                    ],
                    'label' => 'Salvar'
                ]
            )
        ;
    }

    public function getBlockPrefix()
    {
        return 'wspot_segmentation';
    }
}
