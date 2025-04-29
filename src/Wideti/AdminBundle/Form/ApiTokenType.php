<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\ApiWSpotRoles;

class ApiTokenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'required' => true,
                    'label' => 'Título',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'attr' => [
                        'class' => 'span4'
                    ]
                )
            )

            ->add(
                'permissionType',
                ChoiceType::class,
                array(
                    'choices' => [
                        'Leitura (GET)' => ApiWSpotRoles::ROLE_READ,
                        'Escrita (GET, POST, PUT, DELETE)' => ApiWSpotRoles::ROLE_WRITE
                    ],
                    'label' => 'Selecione o tipo de permissão',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required' => true,
                    'attr' => [
                        'class' => 'span4'
                    ]
                )
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
        $resolver->setDefaults([
            'data_class' => "Wideti\\DomainBundle\\Entity\\ApiWSpot"
        ]);
    }

    public function getBlockPrefix()
    {
        return 'wspot_api_token';
    }
}
