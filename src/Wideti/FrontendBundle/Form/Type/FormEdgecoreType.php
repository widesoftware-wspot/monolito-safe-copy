<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormEdgecoreType extends AbstractType
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

        $challenge = $nas->getVendorRawParameters()['challenge'];

        $builder
            ->add(
                'uamip',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $nas->getVendorRawParameters()['uamip']
                    ]
                ]
            )
            ->add(
                'uamport',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $nas->getVendorRawParameters()['uamport']
                    ]
                ]
            )
            ->add(
                'challenge',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $challenge
                    ]
                ]
            )
            ->add(
                'userurl',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $nas->getVendorRawParameters()['userurl']
                    ]
                ]
            )
            ->add(
                'res',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $nas->getVendorRawParameters()['res']
                    ]
                ]
            )
            ->add(
                'username',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $options['username']
                    ]
                ]
            )
            ->add(
                'response',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $this->generateResponse($options['password'], $challenge)
                    ]
                ]
            )
            ->add(
                'button',
                SubmitType::class,
                array(
                    'attr' => array(
                        'value' => 'Login',
                        'class' => 'submit'
                    ),
                )
            );
    }

    private function generateResponse($password, $challenge)
    {
        $hexchal = pack("H32", $challenge);
        return md5("\0" . $password . $hexchal);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'attr'      => [
                    'id' => 'formLoginControladora'
                ],
                'method'    => 'GET',
                'username'  => null,
                'password'  => null
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'edgecore';
    }
}
