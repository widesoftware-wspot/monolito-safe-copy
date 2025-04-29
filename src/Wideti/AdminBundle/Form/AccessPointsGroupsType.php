<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class AccessPointsGroupsType extends AbstractType
{
    use EntityManagerAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $accessPoints = [];
	    $clientId = $options['attr']['client'];

	    $accessPointsGroupRepository = $this->em->getRepository('DomainBundle:AccessPointsGroups');
	    $accessPointsGroupArray = [];
	    $accessPointsGroupArray["Selecione um elemento"] = null;
        $accessPointsGroups = $accessPointsGroupRepository->findBy(['client' => $clientId]);

        foreach ($accessPointsGroups as $accessPointsGroup) {
            $accessPointsGroupArray[$accessPointsGroup->getGroupName()] = $accessPointsGroup->getId();
        }

        if (isset($options['attr']['id'])) {
            $groupID = $options['attr']['id'];

            $accessPointsGroup = $accessPointsGroupRepository->find([
                'id' => $groupID
            ]);

	        foreach ($accessPointsGroup->getAccessPoints() as $accessPoint) {
		        array_push($accessPoints, $accessPoint);
	        }

	        unset($accessPointsGroupArray[$accessPointsGroup->getGroupName()]);
        }

	    $builder
            ->add(
                'groupName',
                TextType::class,
                array(
                    'required' => true,
                    'label'    => 'Nome da regra',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add('parentConfigurations', CheckboxType::class, array(
                'label'    => 'Herdar Configurações?',
                'required' => false,
                'label_attr' => array(
                    'class' => 'control-label'
                )
            ))
            ->add('parentTemplate', CheckboxType::class, array(
                'label'    => 'Herdar Template?',
                'required' => false,
                'label_attr' => array(
                    'class' => 'control-label'
                )
            ))
            ->add(
                'isMaster',
                CheckboxType::class,
                [
                    'label' => 'É regra Master?',
                    'required' => false,
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'template',
                EntityType::class,
                array(
                    'class' => 'DomainBundle:Template',
                    'label' => 'Template',
                    'placeholder' => 'Selecione',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('t')
                            ->innerJoin('t.client', 'c', 'WITH', 'c.id = :client')
                            ->setParameter('client', $options['attr']['client'])
                            ->orderBy('t.name', 'ASC');
                    },
                    'required' => false,
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
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
						    ->setParameter('status', AccessPoints::ACTIVE)
                            ->setMaxResults(10);
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
            ->add('parent', ChoiceType::class, array(
                'choices' => array_flip($accessPointsGroupArray),
                'choices_as_values' => true,
                'label_attr' => array(
                    'class' => 'control-label'
                ),
                'label'    => 'Regra Pai',
                'attr'     => array(
                    'class' => 'span10'
                )
            ))
            ->add(
                'submit',
                SubmitType::class,
                array(
                    'label' => 'Salvar',
                    'attr'   => array(
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                    ),
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
	            'data_class' => 'Wideti\DomainBundle\Entity\AccessPointsGroups'
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'access_point_group';
    }
}
