<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonthFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'month',
                ChoiceType::class,
                array(
                    'required'  => false,
                    'choices'   => array(
                        'Janeiro' => 1,
                        'Fevereiro' => 2,
                        'Março' => 3,
                        'Abril' => 4,
                        'Maio' => 5,
                        'Junho' => 6,
                        'Julho' => 7,
                        'Agosto' => 8,
                        'Setembro' => 9,
                        'Outubro' => 10,
                        'Novembro' => 11,
                        'Dezembro' => 12
                    ),
                    'label' => 'Selecione o mês',
                    'data' => date('m'),
                    'placeholder' => false
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
        return 'monthFilter';
    }
}
