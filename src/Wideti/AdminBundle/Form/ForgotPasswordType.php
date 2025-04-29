<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForgotPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Wideti\DomainBundle\Entity\Users',
                'validation_groups' => function ($form) {
                    return array('forgot_password');
                },
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_forgotpasswordtype';
    }
}
