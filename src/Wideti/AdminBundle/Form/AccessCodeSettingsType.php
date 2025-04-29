<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\AccessCodeSettings;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\BusinessHours\BusinessHoursServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AccessCodeSettingsType extends AbstractType
{
    use EntityManagerAware;
    use BusinessHoursServiceAware;
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity         = null;
        $accessPoints   = null;

        if ($options['attr']['id']) {
            $entity = $this->em
                ->getRepository('DomainBundle:AccessCodeSettings')
                ->find($options['attr']['id']);

            $aps = [];

            /**
             * @var AccessPoints $data
             */
            foreach ($entity->getAccessPoints() as $data) {
                array_push($aps, $data->getId());
            }

            $accessPoints = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointsById($aps);
        }

        $builder
            ->add(
                'enableFreeAccess',
                ChoiceType::class,
                [
                    'choices'   => [
                        'Não' => AccessCodeSettings::STATUS_INACTIVE,
                        'Sim' => AccessCodeSettings::STATUS_ACTIVE
                    ],
                    'label' => 'Habilitar Tempo de Cortesia?',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10'
                    ]
                ]
            )
            ->add(
                'freeAccessTime',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Tempo de Conexão',
                    'attr'     => [
                        'class' => 'span10',
                        'placeholder' => 'ex: 4d 12h 5m'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'freeAccessPeriod',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Período',
                    'attr'     => [
                        'class' => 'span10',
                        'placeholder' => 'ex: 4d 12h 5m'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'endPeriodText',
                TextareaType::class,
                [
                    'label' => 'Texto exibido quando expirar o período de navegação',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => false,
                    'attr' => [
                        'class' => 'span10',
                        'style' => 'height: 80px;'
                    ]
                ]
            )
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
                    'multiple' => true,
                    'attr'     => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => $accessPoints
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Salvar',
                    'attr'   => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                    ]
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'constraints' => new Callback([$this, 'checkEmptyField'])
            ]
        );
    }

    /**
     * @param AccessCodeSettings $data
     * @param ExecutionContextInterface $context
     * @return mixed
     */
    public function checkEmptyField($data, ExecutionContextInterface $context)
    {
        if ($data->isEnableFreeAccess()) {
            if (!$data->getFreeAccessTime()) {
                return $context
                    ->buildViolation(
                        'Preenchimento obrigatório!'
                    )
                    ->atPath('freeAccessTime')
                    ->addViolation();
            } else {
                $value = strtoupper($data->getFreeAccessTime());
                $value = str_replace(['D', 'H', 'M', 'S'], ['DAY', 'HOUR', 'MINUTE', 'SECOND'], $value);

                try {
                    new Period(new \DateTime(), $value);
                } catch (\Exception $ex) {
                    return $context
                        ->buildViolation(
                            'Informe um valor válido, ex: 4d 12h 5m'
                        )
                        ->atPath('freeAccessTime')
                        ->addViolation();
                }
            }

            if (!$data->getFreeAccessPeriod()) {
                return $context
                    ->buildViolation(
                        'Preenchimento obrigatório!'
                    )
                    ->atPath('freeAccessPeriod')
                    ->addViolation();
            } else {
                $value = strtoupper($data->getFreeAccessPeriod());
                $value = str_replace(['D', 'H', 'M', 'S'], ['DAY', 'HOUR', 'MINUTE', 'SECOND'], $value);

                try {
                    new Period(new \DateTime(), $value);
                } catch (\Exception $ex) {
                    return $context
                        ->buildViolation(
                            'Informe um valor válido, ex: 4d 12h 5m'
                        )
                        ->atPath('freeAccessPeriod')
                        ->addViolation();
                }
            }
        }

        if ($data->getInAccessPoints() && count($data->getAccessPoints()) == 0) {
            return $context
                ->buildViolation(
                    'Selecione ao menos 1 ponto de acesso'
                )
                ->atPath('accessPoint')
                ->addViolation();
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_access_code_settings';
    }
}
