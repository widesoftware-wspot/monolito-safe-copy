<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignViewsFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'date_from',
                DateType::class,
                [
                    'disabled' => true,
                    'label'  => 'Acessos entre',
                    'label_attr' => array(
                        'class' => 'filterRange'
                    ),
                    'required' => true,
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'data' => new \DateTime("NOW -30 days"),
                    'attr'   => [ 'class' => 'input-mini' ],
                ]
            )
            ->add(
                'date_to',
                DateType::class,
                [
                    'disabled' => true,
                    'label'  => 'e',
                    'label_attr' => array(
                        'class' => 'filterRange'
                    ),
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => true,
                    'data' => new \DateTime("NOW"),
                    'attr'   => [
                        'class' => 'input-mini',
                    ],
                ]
            )
            ->add(
                'filtrar',
                SubmitType::class,
                [
                    'attr' => [ 'class' => 'btn btn-default' ]
                ]
            );

        $builder->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([ 'csrf_protection' => false ]);
    }

    public function getBlockPrefix()
    {
        return 'campaignViewsReportsFilter';
    }
}
