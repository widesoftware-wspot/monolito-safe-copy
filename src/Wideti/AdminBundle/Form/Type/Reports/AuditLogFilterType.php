<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuditLogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $options['em'];
        $client = $options['client'];
    
        $dateFrom = new \DateTime("NOW -30 days");
        $dateTo   = new \DateTime("NOW");

        $users = $em
            ->getRepository('DomainBundle:Users')
            ->listAllUsers(
                $client->getId(),
                null, null, null, null, ''
            );
        $userChoices = [];
        foreach ($users as $user) {
            $userChoices[$user->getUsername()] = $user->getId();
        }

        $builder
            // Filtro para o usuário
            ->add(
                'user',
                ChoiceType::class,
                [
                    'required'  => false,
                    'choices'   => $userChoices,
                    'label'     => 'Usuário',
                    'placeholder' => 'Selecione um usuário',
                    'attr' => [
                        'style' => 'width:100%'
                    ]
                ]
            )

            // Campo para o tipo de evento (event_type)
            ->add(
                'event_type',
                ChoiceType::class,
                [
                    'required'  => false,
                    'choices'   => [
                        'Create' => 'create',
                        'Update' => 'update',
                        'Delete' => 'delete',
                        'System' => 'system',
                    ],
                    'label'       => 'Tipo de evento',
                    'placeholder' => 'Todos',
                    'attr' => [
                        'style' => 'width:100%'
                    ]
                ]
            )
            // Campo para a data de início (date_from)
            ->add(
                'date_from',
                DateType::class,
                [
                    'label'     => 'De',
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
            // Campo para a data de término (date_to)
            ->add(
                'date_to',
                DateType::class,
                [
                    'label'    => 'Até',
                    'widget'   => 'single_text',
                    'format'   => 'dd/MM/yyyy',
                    'required' => false,
                    'data'     => $dateTo,
                    'attr'     => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            // Botão de submissão
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
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => null, 
            'client' => null,
            'em' => null
        ]);
    }

    public function getBlockPrefix()
    {
        return 'auditReportFilter';
    }
}
