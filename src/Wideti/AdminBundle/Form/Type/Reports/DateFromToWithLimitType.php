<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\AccessPoints;

class DateFromToWithLimitType extends AbstractType
{
    protected $em;
    protected $client;

    public function __construct(EntityManager $em)
    {
        $this->em       = $em;
    }

    public function accessPointsList()
    {
        $entities = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->listAll($this->client, null, null, [
                'status' => AccessPoints::ACTIVE
            ]);

        $apGroups = ["Sem Grupo" => []];

        foreach ($entities as $ap) {
            $group = "Sem Grupo";

            if ($ap->getGroup()) {
                $group = $ap->getGroup()->getGroupName();
            }
            if (!isset($apGroups[$group])) {
                $apGroups[$group] = [];
            }

            $apGroups[$group][$ap->getFriendlyName()] = $ap->getId();
        }
        return $apGroups;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['attr']['dashboard'] == 1) {
            $dateFrom = "NOW -6 days";
        } else {
            $dateFrom = "NOW -30 days";
        }
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $options['attr']['client']
        ]);
        $this->client = $client;

        $builder
            ->add(
                'access_point',
                ChoiceType::class,
                [
                    'label'     => 'Ponto de acesso',
                    'multiple'  => true,
                    'required'  => false,
                    'attr'      => [
                        'multiple'  => 'multiple'
                    ],
                    'choices'   => $this->accessPointsList()
                ]
            )
            ->add(
                'date_from',
                DateType::class,
                [
                    'label'     => 'Início',
                    'required'  => true,
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => new \DateTime($dateFrom),
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
                    'label'     => 'Fim',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'data'      => new \DateTime("NOW"),
                    'required'  => true,
                    'attr'      => [
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
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
                'constraints'     => new Callback([$this, 'checkFieldFormat'])
            ]
        );
    }

    public function checkFieldFormat($data, ExecutionContextInterface $context)
    {
        $dateFrom = isset($data['date_from']) ? $data['date_from'] : '';
        $dateTo = isset($data['date_to']) ? $data['date_to'] : '';

        if (!empty($dateTo) && !empty($dateFrom)) {
            $dateFrom->diff($dateTo);
            $interval = $dateFrom->diff($dateTo);

            if ($interval->format('%a') > 92) {
                return $context
                    ->buildViolation(
                        'O período limite para consultas é de 3 meses'
                    )
                    ->atPath('')
                    ->addViolation();
            }
        }
    }

    public function getBlockPrefix()
    {
        return 'dateFromToWithLimitFilter';
    }
    
}
