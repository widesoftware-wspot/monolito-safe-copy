<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Helpers\DateHelper;
use Wideti\DomainBundle\Helpers\ValidationHelper;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AccessCodeType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity         = null;
        $accessPoints   = null;
        $preDefinedCode = '';



        if ($options['attr']['id']) {
            $entity = $this->em
                ->getRepository('DomainBundle:AccessCode')
                ->find($options['attr']['id']);

            $aps = [];

            foreach ($entity->getAccessPoints() as $data) {
                array_push($aps, $data->getId());
            }

            $accessPoints = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointsById($aps);

            $preDefinedCode = isset($options['attr']['code']) ? $options['attr']['code'] : '';
        }
        $builder
            ->add(
                'enable',
                ChoiceType::class,
                [
                    'choices'   => [
                        'Ativo' => AccessCode::ACTIVE,
                        'Inativo' => AccessCode::INACTIVE
                    ],
                    'label' => 'Status',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10'
                    ],
                    'data' => ($entity) ? $entity->getEnable() : null
                ]
            )
            ->add(
                'step',
                ChoiceType::class,
                [
                    'choices'   => [
                        'Primeira tela' => AccessCode::STEP_LOGIN,
                        'Após o cadastro' => AccessCode::STEP_SIGNUP
                    ],
                    'label' => 'Em qual momento solicitaremos o código para o visitante?',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10'
                    ],
                    'data' => ($entity) ? $entity->getStep() : null
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices'   => [
                        'Predefinido' => AccessCode::TYPE_PREDEFINED,
                        'Aleatórios' => AccessCode::TYPE_RANDOM
                    ],
                    'label' => 'Tipo de código',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10'
                    ],
                    'data' => ($entity) ? $entity->getType() : null
                ]
            )
            ->add(
                'quantity',
                TextType::class,
                [
                    'label' => 'Quantidade',
                    'label_attr' => [
                        'class' => 'control-label',
                    ],
                    'required'  => true,
                    'attr' => [
                        'placeholder' => 'Quantidade máxima de 1000 (Mil)',
                        'class' => 'span10',
                        'maxlength' => 4
                    ],
                    'data' => ($entity) ? $entity->getQuantity() : null
                ]
            )
            ->add(
                'connectionTime',
                TextType::class,
                [
                    'label' => 'Tempo de conexão',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => false,
                    'attr' => [
                        'placeholder' => '(ex. 4d 12h 5m)',
                        'class' => 'span10'
                    ],
                    'data' => ($entity) ? $entity->getConnectionTime() : null
                ]
            )
            ->add(
                'periodFrom',
                DateType::class,
                [
                    'required'  => false,
                    'label'     => 'Vigência (Início)',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'attr'      => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => ($entity) ? $entity->getPeriodFrom() : null
                ]
            )
            ->add(
                'periodTo',
                DateType::class,
                [
                    'required'  => false,
                    'label'     => 'Vigência (Fim)',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'attr'      => [
                        'class' => 'span10',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => ($entity) ? $entity->getPeriodTo() : null
                ]
            )
            ->add(
                'inAccessPoints',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices'   => [
                        'Todos' => "",
                        'Escolher pontos de acesso' => 1
                    ],
                    'placeholder' => false,
                    'label' => 'Pontos de acesso',
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
                    'required' => false,
                    'class'    => 'DomainBundle:AccessPoints',
                    'label'    => 'Selecionar pontos',
                    'placeholder'   => 'Selecione',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('a')
                            ->innerJoin('a.client', 'c', 'WITH', 'c.id = :client')
                            ->where('a.status = :status')
                            ->orderBy('a.friendlyName', 'ASC')
                            ->setParameter('client', $options['attr']['client'])
                            ->setParameter('status', AccessPoints::ACTIVE);
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
                'fileLogotipo',
                FileType::class,
                [
                    'required'      => false,
                    'label'         => 'Logotipo',
                    'attr'     => [
                        'class' => 'span12'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add('logotipo', HiddenType::class)
            ->add(
                'code',
                TextType::class,
                [
                    'label' => 'Código',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span10',
                        'value' => $preDefinedCode
                    ],
                ]
            )
            ->add(
                'backgroundColor',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Cor de fundo',
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off',
                        'style' => 'width: 165px; margin-right: 30px;'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => ($entity) ? $entity->getBackgroundColor() : null
                ]
            )
            ->add(
                'fontColor',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Cor da fonte',
                    'attr'     => [
                        'class' => 'span8',
                        'autocomplete' => 'off',
                        'style' => 'width: 165px; margin-right: 30px;'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => ($entity) ? $entity->getFontColor() : null
                ]
            )
            ->add(
                'text',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'Texto',
                    'attr'     => [
                        'class' => 'span12',
                        'autocomplete' => 'off',
                        'style' => 'width: 570px;'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => ($entity) ? $entity->getText() : null
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => ['class' => 'btn btn-icon btn-primary glyphicons circle_ok'],
                    'label' => 'Salvar'
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
                'data_class' => AccessCode::class,
                'constraints' => new Callback([$this, 'checkFieldFormat']),
                'allow_extra_fields' => true
            ]
        );
    }

    public function checkFieldFormat(AccessCode $data, ExecutionContextInterface $context)
    {
        $type           = $data->getType();
        $quantity       = $data->getQuantity();
        $connectionTime = $data->getConnectionTime();
        $periodFrom     = $data->getPeriodFrom();
        $periodTo       = $data->getPeriodTo();

        if ($type == 'random') {
            if ($quantity < 1 || $quantity > 1000) {
                return $context
                    ->buildViolation(
                        'A quantidade deverá ser entre 1 e 1000'
                    )
                    ->atPath('quantity')
                    ->addViolation();
            }

            if ($quantity < 1 || $quantity > 1000) {
                return $context
                    ->buildViolation(
                        'A quantidade deverá ser entre 1 e 1000'
                    )
                    ->atPath('quantity')
                    ->addViolation();
            }
        }

        if ($connectionTime) {
            $value = strtoupper($connectionTime);
            $value = str_replace(['D', 'H', 'M', 'S'], ['DAY', 'HOUR', 'MINUTE', 'SECOND'], $value);

            try {
                new Period(new \DateTime(), $value);
            } catch (\Exception $ex) {
                return $context
                    ->buildViolation(
                        'Informe um valor válido no campo [Tempo de conexão], ex: 4d 12h 5m'
                    )
                    ->atPath('connectionTime')
                    ->addViolation();
            }
        }

        if ($periodTo) {
            if (date_format($periodTo, 'Y-m-d') < date_format($periodFrom, 'Y-m-d')) {
                return $context
                    ->buildViolation(
                        'A data final deve ser maior que a data de início'
                    )
                    ->atPath('periodTo')
                    ->addViolation();
            }
        }

        if ($data->getInAccessPoints() && count($data->getAccessPoints()) == 0) {
            return $context
                ->buildViolation(
                    'Selecione ao menos 1 ponto de acesso'
                )
                ->atPath('accessPoints')
                ->addViolation();
        }
        if ($type == 'predefined' && ValidationHelper::containsSpecialCharacter($data->getCode()) ){
            return $context
                    ->buildViolation(
                        'Caracteres especiais não são aceitos'
                    )
                    ->atPath('code')
                    ->addViolation();
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_access_code';
    }
}