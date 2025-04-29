<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class ReportFilterType extends AbstractType
{
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [
            'framedipaddress' => 'IP',
            'callingstationid' => 'Mac Address',
            'calledstation_name' => 'Ponto de acesso'
        ];

        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->getLoginField();

        foreach ($fields as $field) {
            $choices['properties.' . $field->getIdentifier()]
                = $field->getNames()['pt_br'];
        }

        $builder
            ->add(
                'filter',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices' => array_flip($choices),
                    'placeholder' => 'Escolha uma opção',
                    'label' => 'Filtros'
                ]
            )
            ->add(
                'value',
                TextType::class,
                [
                    'label' => false,
                    'required' => false
                ]
            )
            ->add(
                'access_point',
                EntityType::class,
                [
                    'label' => 'Ponto de acesso',
                    'required' => false,
                    'class' => 'Wideti\DomainBundle\Entity\AccessPoints',
                    'placeholder' => 'Todos',
                    'query_builder' => function (EntityRepository $er) use (
                        $options
                    ) {
                        return $er->createQueryBuilder('ap')
                            ->where('ap.status = 1')
                            ->innerJoin('ap.client', 'c', 'WITH',
                                'c.id = :client')
                            ->setParameter('client', $options['attr']['client'])
                            ->orderBy('ap.friendlyName', 'ASC');
                    },
                    'label_attr' => [
                        'class' => 'labelAccessPoints'
                    ]
                ]
            )
            ->add(
                'date_from',
                DateType::class,
                [
                    'label' => 'Período de',
                    'required' => true,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'data' => new \DateTime("NOW -30 days"),
                    'attr' => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'date_to',
                DateType::class,
                [
                    'label' => 'até',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => true,
                    'data' => new \DateTime("NOW"),
                    'attr' => [
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
        $resolver->setDefaults([
            'csrf_protection' => false,
            'constraints' => new Callback([$this, 'checkFieldFormat'])
        ]);
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
        return 'reportsFilter';
    }

}
