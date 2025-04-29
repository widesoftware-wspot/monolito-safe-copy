<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmsReportFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'date_from',
                DateType::class,
                array(
                    'label'  => 'Visitas entre',
                    'required' => true,
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'data' => new \DateTime("NOW -30 days"),
                    'attr'   => array(
                        'autocomplete' => 'off',
                        'class' => 'input-mini',
                    ),
                )
            )
            ->add(
                'date_to',
                DateType::class,
                array(
                    'label'  => 'até',
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => true,
                    'data' => new \DateTime("NOW"),
                    'attr'   => array(
                        'autocomplete' => 'off',
                        'class' => 'input-mini',
                    ),
                )
            )
            ->add(
                'filtrar',
                SubmitType::class,
                array(
                    'attr' => array(
                        'class' => 'btn btn-default',
                    ),
                )
            );

        $builder->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array( 'csrf_protection' => false ));
    }

    public function getBlockPrefix()
    {
        return 'smsReportsFilter';
    }
}
