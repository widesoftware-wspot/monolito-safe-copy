<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormEnterasysType extends AbstractType
{
    use SessionAware;

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

        /**
         * @var Client $client
         */
        $client = $this->session->get("wspotClient");

        $builder
            ->add(
                'token',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(Nas::EXTRA_PARAM_TOKEN),
                    ),
                )
            )
            ->add(
                'role',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => 'WSPOT',
                    ),
                )
            )
            ->add(
                'wlan',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => '11',
                    ),
                )
            )
            ->add(
                'opt27',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => '0',
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
                'dest',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $client->getDomain() . ".mambowifi.com/302",
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
                'method' => 'GET',
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
        return 'enterasys';
    }
}
