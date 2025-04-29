<?php

namespace Wideti\AdminBundle\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserFilterType extends AbstractType
{

    /**
    * @param FormBuilderInterface $builder
    * @param array $options
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'filtro',
            ChoiceType::class,
            array(
                'choices'      => array(
                    'Nome'  => 'nome',
                    'E-mail' => 'email'
                ),
                'placeholder' => 'Escolha uma opção',
                )
        )
        ->add(
            'value',
            TextType::class,
            array(
                'label'  => ' ',
                'attr'   => array(
                    'class' => 'input-mini',
                        'style' => 'width: 250px;',
                    ),
                )
        )
        ->add(
            'Filtrar',
            SubmitType::class,
            array(
                'attr' => array(
                    'class' => 'btn btn-default',
                ),
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_usuarios_filter';
    }
}
