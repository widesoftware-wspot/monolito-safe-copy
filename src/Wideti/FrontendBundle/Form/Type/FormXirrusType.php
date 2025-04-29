<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\FrontendBundle\Factory\NasHandlers\XirrusHandler;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormXirrusType extends AbstractType
{
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $uamsecret  = XirrusHandler::UAMSECRET;
        $challenge  = $this->session->get('xirrusChallenge');
        $password   = NasHelper::xirrusEncrypting($options['password'], $challenge, $uamsecret);

        $builder
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
                'uamip',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $this->session->get('uamip')
                    ]
                ]
            )
            ->add(
                'uamport',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $this->session->get('uamport')
                    ]
                ]
            )
            ->add(
                'userurl',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $this->session->get('xirrus_redirect_url')
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
                'password',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'value' => $password
                    ]
                ]
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
            [
                'csrf_protection' => false,
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
        return 'xirrus';
    }
}
