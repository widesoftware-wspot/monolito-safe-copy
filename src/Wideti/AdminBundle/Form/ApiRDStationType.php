<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\ApiRDStation;
use Wideti\DomainBundle\Service\ApiRDStation\ApiRDStationServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ApiRDStationType extends AbstractType
{
    use EntityManagerAware;
    use ApiRDStationServiceAware;
    use SessionAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity         = null;
        $accessPoints   = null;

        if ($options['attr']['id']) {
            $entity = $this->em
                ->getRepository('DomainBundle:ApiRDStation')
	            ->findOneBy(['id' => $options['attr']['id']]);

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
                'title',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'Título',
                    'attr'     => [
                        'class' => 'span10'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => $entity ? $entity->getTitle() : ''
                ]
            )
            ->add(
                'token',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'API Token (RD)',
                    'attr'     => [
                        'class' => 'span10'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'data' => $entity ? $entity->getToken() : ''
                ]
            )
            ->add(
                'enableAutoIntegration',
                ChoiceType::class,
                [
                    'choices' => [
                        'Não' => 0,
                        'Sim' => 1
                    ],
                    'required'  => true,
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'label' => 'Habilitar Integração?',
                    'data' => $entity ? $entity->isEnableAutoIntegration() : 0
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

	/**
	 * @param ApiRDStation $data
	 * @param ExecutionContextInterface $context
	 * @return mixed
	 */
    public function checkFields($data, ExecutionContextInterface $context)
    {
	    $client     = $this->getLoggedClient();
        $id         = $this->session->get('apiRDStationId');
        $entities   = $this->em
            ->getRepository('DomainBundle:ApiRDStation')
            ->findBy(['client' => $client]);

        if (
            ($data->getInAccessPoints() == 0 && $this->apiRDStationService->checkAccessPointAlreadyExists($client, $id, null)) ||
            ($data->getInAccessPoints() == 1 && $this->apiRDStationService->checkAccessPointAlreadyExists($client, $id, null))
        ) {
            return $context
                ->buildViolation(
                    'Já existe uma integração criada com Todos Pontos de Acesso vinculados'
                )
                ->atPath('wspot_api_rd_station_accessPoints')
                ->addViolation();
        }

        if ($data->getInAccessPoints() == 0) {
            if ($id) {
                foreach ($entities as $entity) {
                    if ($id != $entity->getId()) {
                        return $context
                            ->buildViolation(
                                'Já existe uma integração criada com Ponto de Acesso vinculado'
                            )
                            ->atPath('wspot_api_rd_station_accessPoints')
                            ->addViolation();
                    }
                }
            } else {
                if ($data->getInAccessPoints() == 0 && count($entities) > 0) {
                    return $context
                        ->buildViolation(
                            'Já existe uma integração criada com Ponto de Acesso vinculado'
                        )
                        ->atPath('wspot_api_rd_station_accessPoints')
                        ->addViolation();
                }
            }
        }

	    /**
	     * @var AccessPoints $ap
	     */
        foreach ($data->getAccessPoints() as $ap) {
            if ($this->apiRDStationService->checkAccessPointAlreadyExists($client, $id, $ap)) {
                return $context
                    ->buildViolation(
                        'Ponto de Acesso (' .$ap->getFriendlyName(). ') já está cadastrado em outra integração'
                    )
                    ->atPath('wspot_api_rd_station_accessPoints')
                    ->addViolation();
            }
        }

        if ($data->getInAccessPoints() == 1 && count($data->getAccessPoints()) == 0) {
            return $context
                ->buildViolation(
                    'Selecione ao menos 1 ponto de acesso'
                )
                ->atPath('wspot_api_rd_station_accessPoints')
                ->addViolation();
        }
    }

    public function getBlockPrefix()
    {
        return 'wspot_api_rd_station';
    }
}
