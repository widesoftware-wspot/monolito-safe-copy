<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DashboardFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateFrom   = "NOW -30 days";
        $dateTo     = "NOW";

        if (array_key_exists('data', $options)) {
            $dateFrom   = str_replace('/', '-', $options['data']['date_from']);
            $dateTo     = str_replace('/', '-', $options['data']['date_to']);
        }

        $builder
            ->add(
                'filtrar',
                SubmitType::class,
                array(
                    'attr' => array(
                        'class' => 'btn btn-default'
                    )
                )
            )
            ->add(
                'date_to',
                DateType::class,
                array(
                    'disabled' => true,
                    'label'     => 'Até: ',
                    'label_attr' => array(
                        'class' => 'filterRange hide'
                    ),
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => new \DateTime($dateTo),
                    'required'  => false,
                    'attr'      => array(
                        'class' => 'input-mini span2 filterRange hide'
                    )
                )
            )
            ->add(
                'date_from',
                DateType::class,
                array(
                    'disabled' => true,
                    'label'     => 'De: ',
                    'label_attr' => array(
                        'class' => 'filterRange hide'
                    ),
                    'required'  => false,
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => new \DateTime($dateFrom),
                    'attr'      => array(
                        'class' => 'input-mini span2 filterRange hide'
                    )
                )
            )
            ->add(
                'filter',
                ChoiceType::class,
                array(
                    'required'  => false,
                    'choices'   => array(
                        'Últimos 30 dias' => 'last30days',
                        'Customizado' => 'custom'
                    ),
                    'label'       => false,
                    'placeholder' => 'Todo Período',
                    'attr' => array(
                        'class' => 'input-mini span2',
                        'style' => 'margin-right: 10px'
                    )
                )
            )
        ;

        $builder->setMethod('GET');
    }

    public function getBlockPrefix()
    {
        return 'dashboardFilter';
    }
}
