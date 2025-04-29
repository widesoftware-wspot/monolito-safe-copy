<?php

namespace Wideti\PanelBundle\Form\Type;

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
use Wideti\DomainBundle\Entity\Client;

class ClientFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $client = new Client();

        $builder
            ->add(
                'value',
                TextType::class,
                [
                    'label'     => false,
                    'required'  => false,
                    'attr'      => [
                        'placeholder' => 'Busque por clientes',
                        'style' => 'width:260px;'
                    ]
                ]
            )
            ->add(
                'option',
                ChoiceType::class,
                [
                    'required'      => false,
                    'choices'       => [
                        'Domínio' => 'domain',
                        'ERP Id' => 'erpId',
                        'Razão Social' => 'company',
                        'Nome/E-mail do Administrador' => 'adminData',
                    ],
                    'placeholder'   => 'Selecione',
                    'label'         => 'Filtrar por',
	                'attr'     => [
		                'class' => 'span12',
		                'autocomplete' => 'off'
	                ],
	                'label_attr' => [
		                'class' => 'control-label'
	                ]
                ]
            )->add(
		        'plan',
		        EntityType::class,
		        [
			        'class' => 'DomainBundle:Plan',
			        'required'  => false,
			        'label' => 'Plano',
			        'placeholder' => 'Selecione',
			        'query_builder' => function (EntityRepository $er) use ($options){
				        return $er->createQueryBuilder('p')
					        ->orderBy('p.plan');
			        },
			        'attr'     => [
				        'class' => 'span12',
				        'autocomplete' => 'off'
			        ],
			        'label_attr' => [
				        'class' => 'control-label'
			        ]
		        ]
	        )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'required'      => false,
                    'choices'       =>  $client->getStatusArray(),
                    'placeholder'   => 'Selecione',
                    'label'         => 'Status',
	                'attr'     => [
		                'class' => 'span12',
		                'autocomplete' => 'off'
	                ],
	                'label_attr' => [
		                'class' => 'control-label'
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
        ]);
    }

    public function getBlockPrefix()
    {
        return 'clientFilter';
    }
}
