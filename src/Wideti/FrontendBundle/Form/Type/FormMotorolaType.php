<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormMotorolaType extends AbstractType
{
    use SessionAware;

    protected $formFactory;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        $builder
            ->add(
                'f_user',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $options['username']
                    )
                )
            )
            ->add(
                'f_pass',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $options['password']
                    )
                )
            )
            ->add(
                'f_hs_server',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getNasFormPost()->getIp()
                    )
                )
            )
            ->add(
                'hs_server',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getNasFormPost()->getIp()
                    )
                )
            )
            ->add(
                'f_Qv',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(Nas::EXTRA_PARAM_QV)
                    )
                )
            )
            ->add(
                'Qv',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(Nas::EXTRA_PARAM_QV)
                    )
                )
            )
            ->add(
                'agree',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => 'yes'
                    )
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
                    'id' => 'formLoginControladora'
                ),
                'method' => 'POST',
                'username' => null,
                'password' => null,
                'csrf_protection' => false
            )
        );
    }

    /**
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return "motorola";
    }
}