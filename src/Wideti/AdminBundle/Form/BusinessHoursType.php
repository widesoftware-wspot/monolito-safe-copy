<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Helpers\BusinessHoursHelper;
use Wideti\DomainBundle\Service\BusinessHours\BusinessHoursServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class BusinessHoursType extends AbstractType
{
    use EntityManagerAware;
    use BusinessHoursServiceAware;
    use SessionAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity         = null;
        $accessPoints   = null;

        if ($options['attr']['id']) {
            $entity = $this->em
                ->getRepository('DomainBundle:BusinessHours')
                ->getById($options['attr']['id']);

            $aps = [];

            foreach ($entity->getAccessPoints() as $data) {
                array_push($aps, $data->getId());
            }

            $accessPoints = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointsById($aps);
        }

        $days = [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday'
        ];

        foreach ($days as $day) {
            $builder->add($day, CollectionType::class, [
                'entry_type' => TimeRangeType::class,
                'entry_options' => ['label' => false, 'max_items' => 3],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => [
                    'data-prototype' => $builder->create($day, TimeRangeType::class)->getForm()->createView(),
                ],
                'data' => ($entity) ? BusinessHoursHelper::getHours($entity, $day) : [['from' => '00:00', 'to' => '23:59']]
            ]);
        }

        $builder
            ->add(
                'inAccessPoints',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices'  => [
                        'Todos' => "",
                        'Escolher pontos de acesso' => 1
                    ],
                    'placeholder' => false,
                    'label' => 'Pontos de Acesso',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span10'
                    ],
                    'data' => ($entity) ? $entity->getInAccessPoints() : null
                ]
            )
            ->add(
                'accessPoints',
                EntityType::class,
                [
                    'required'      => false,
                    'class'         => 'DomainBundle:AccessPoints',
                    'label'         => 'Selecionar pontos',
                    'placeholder'   => 'Selecione',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('a')
                            ->innerJoin('a.client', 'c', 'WITH', 'c.id = :client')
                            ->setParameter('client', $options['attr']['client'])
                            ->orderBy('a.friendlyName', 'ASC');
                    },
                    'multiple'      => true,
                    'attr'          => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr'    => [
                        'class' => 'control-label'
                    ],
                    'data' => $accessPoints
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'constraints' => new Callback([$this, 'checkFields'])
            ]
        );
    }

    public function checkFields($data, ExecutionContextInterface $context)
    {
	    $id = $this->session->get('businessHoursId');
	    $client = $this->getLoggedClient();

	    $entities = $this->em
            ->getRepository('DomainBundle:BusinessHours')
            ->findBy([
            	'client' => $client
            ]);

	    if (
            ($data['inAccessPoints'] == 0 && $this->businessHoursService->checkAccessPointAlreadyExists($client, $id, null))
            || ($data['inAccessPoints'] == 1 && $this->businessHoursService->checkAccessPointAlreadyExists($client, $id, null))
        ) {
		    return $context
                ->buildViolation(
                    'Já existe uma faixa de horário com Todos Pontos de Acesso vinculados'
                )
                ->atPath('wspot_business_hours_accessPoints')
                ->addViolation();
        }

        if ($data['inAccessPoints'] == 0) {
            if ($id) {
                foreach ($entities as $entity) {
                	if ($id != $entity->getId()) {
                        return $context
                            ->buildViolation(
                                'Já existe uma faixa de horário com Ponto de Acesso vinculado'
                            )
                            ->atPath('wspot_business_hours_accessPoints')
                            ->addViolation();
                    }
                }
            } else {
                if ($data['inAccessPoints'] == 0 && count($entities) > 0) {
                    return $context
                        ->buildViolation(
                            'Já existe uma faixa de horário com Ponto de Acesso vinculado'
                        )
                        ->atPath('wspot_business_hours_accessPoints')
                        ->addViolation();
                }
            }
        }

        foreach ($data['accessPoints'] as $ap) {
            if ($this->businessHoursService->checkAccessPointAlreadyExists($client, $id, $ap)) {
                return $context
                    ->buildViolation(
                        'Ponto de Acesso (' .$ap->getFriendlyName(). ') já está cadastrado em outra faixa de horário'
                    )
                    ->atPath('wspot_business_hours_accessPoints')
                    ->addViolation();
            }
        }

        if ($data['inAccessPoints'] == 1 && count($data['accessPoints']) == 0) {
            return $context
                ->buildViolation(
                    'Selecione ao menos 1 ponto de acesso'
                )
                ->atPath('wspot_business_hours_accessPoints')
                ->addViolation();
        }

        $daysOfWeek = [
            'monday' => 'Segunda-feira',
            'tuesday'=> 'Terça-feira',
            'wednesday'=> 'Quarta-feira',
            'thursday'=> 'Quinta-feira',
            'friday'=> 'Sexta-feira',
            'saturday'=> 'Sábado',
            'sunday'=> 'Domingo'
        ];
        foreach ($data as $day => $dayIntervals) {
            if (isset($daysOfWeek[$day])) {
                foreach ($dayIntervals as $index => $timeRange) {
                    $from = new \DateTime($timeRange['from']);
                    $to = new \DateTime($timeRange['to']);
    
                    // Verifica se o intervalo é válido (from < to)
                    if ($from >= $to) {
                        $context->buildViolation(sprintf('Horário inválido na %s.', ucfirst($daysOfWeek[$day])))
                            ->atPath('wspot_business_hours_' .  $day . '_' . $index . '_to')
                            ->addViolation();
                    }
    
                    // Verifica conflitos com outros intervalos do mesmo dia
                    foreach ($dayIntervals as $index2 => $timeRange2) {
                        if ($index != $index2) {
                            $from2 = new \DateTime($timeRange2['from']);
                            $to2 = new \DateTime($timeRange2['to']);
    
                            if (($from <= $to2 && $to >= $from2) || ($from2 <= $to && $to2 >= $from)) {
                                $context->buildViolation(sprintf('Conflito de horários na %s.', ucfirst($daysOfWeek[$day])))
                                    ->atPath('wspot_business_hours_' .  $day . '_' . $index . '_to')
                                    ->addViolation();
                            }
                        }
                    }
                }
            }
        }
    }

    public function getBlockPrefix()
    {
        return 'wspot_business_hours';
    }
}
