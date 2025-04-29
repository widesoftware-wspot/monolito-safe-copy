<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormGrandstreamType extends AbstractType
{
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        $challenge = $nas->getVendorRawParameters()['challenge'];

        $builder
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
                'orig_url',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $redirectUrl,
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'attr' => array(
                    'id' => 'formLoginControladora',
                ),
                'method' => 'GET',
                'username' => null,
                'password' => null,
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'grandstream';
    }
}