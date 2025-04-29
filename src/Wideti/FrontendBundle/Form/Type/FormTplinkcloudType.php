<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormTplinkcloudType extends AbstractType
{
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    protected $formFactory;
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        $redirectUrl = $this->session->get('redirectUrl');

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
                'redirectUrl',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $redirectUrl,
                    ),
                )
            )
            ->add(
                'clientIp',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam('clientIp'),
                    ),
                )
            )
            ->add(
                'clientMac',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getGuestDeviceMacAddress(),
                    ),
                )
            )
            ->add(
                'ap',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getAccessPointMacAddress(),
                    ),
                )
            )
            ->add(
                'ssid',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam('ssid'),
                    ),
                )
            )
            ->add(
                'radioId',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam('radioId'),
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
        return 'tp_link_cloud';
    }
}