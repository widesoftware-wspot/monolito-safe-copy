<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormCiscoCatalystType extends AbstractType
{
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'redirect_url',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $this->session->get('redirect_url'),
                    ),
                )
            )
            ->add(
                'buttonClicked',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => 4,
                    ),
                )
            )
            ->add(
                'err_flag',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => 0,
                    ),
                )
            )
            ->add(
                'username',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $options['username'],
                    ),
                )
            )
            ->add(
                'password',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $options['password'],
                    ),
                )
            )
            ->add(
                'submit',
                SubmitType::class,
                array(
                    'attr' => array(
                        'value' => 'Sign In'
                    )
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'attr' => array(
                    'id' => 'formLoginControladora',
                ),
                'method' => 'POST',
                'username' => null,
                'password' => null,
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'cisco_catalyst';
    }
}
