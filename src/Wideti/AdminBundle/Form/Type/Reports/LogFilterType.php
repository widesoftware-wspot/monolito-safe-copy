<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'module',
                ChoiceType::class,
                array(
                    'required' => false,
                    'choices' => array(
                        'Visitantes' => 'Guests',
                        'Campanhas' => 'Campaign',
                        'Configurações' => 'Configuration',
                        'Administradores' => 'Users'
                    ),
                    'placeholder' => 'Escolha uma opção',
                    'label'       => 'Filtros'
                )
            )
            ->add(
                'date_from',
                DateType::class,
                array(
                    'label'  => 'Período de',
                    'required' => false,
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'data' => new \DateTime("NOW -30 days"),
                    'attr'   => array(
                        'class' => 'input-mini'
                    )
                )
            )
            ->add(
                'date_to',
                DateType::class,
                array(
                    'label'  => 'até',
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                    'data' => new \DateTime("NOW"),
                    'attr'   => array(
                        'class' => 'input-mini'
                    )
                )
            )
            ->add(
                'filtrar',
                SubmitType::class,
                array(
                    'attr' => array(
                        'class' => 'btn btn-default'
                    )
                )
            );

        $builder->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'constraints' => new Callback([$this, 'validateForm'])
        ]);
    }

    public function validateForm($data, ExecutionContextInterface $context)
    {
        $period = date_diff($data['date_from'], $data['date_to']);

        if ((int)$period->format('%d') > 30) {
            $context->buildViolation('Você não pode selecionar um período maior que 30 dias')
                ->atPath('module')
                ->addViolation()
            ;
        }
    }

    public function getBlockPrefix()
    {
        return 'logs_filter';
    }
}
