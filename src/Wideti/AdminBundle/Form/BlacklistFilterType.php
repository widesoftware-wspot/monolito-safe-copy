<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlacklistFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'macAddress',
            TextType::class,
            array(
                'label'  => 'Mac Address',
                'attr'   => array(
                    'class' => 'input-mini',
                    'style' => 'width: 250px;',
                ),
            )
        );

        $builder->add(
            'Filtrar',
            SubmitType::class,
            array(
                'attr' => array(
                    'class' => 'btn btn-default',
                ),
            )
        );

        $builder->setMethod('GET');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
            'csrf_protection' => false,
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_blacklist_form_filter';
    }
}
