<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\Tplinkv4Fields;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormTplinkv4Type extends AbstractType
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
                'originUrl',
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
                        'value' =>  $nas->getExtraParam(Tplinkv4Fields::EXTRA_PARAM_CLIENT_IP),
                    ),
                )
            )
            ->add(
                'gatewayMac',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  '',
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
                'apMac',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getAccessPointMacAddress(),
                    ),
                )
            )
            ->add(
                'ssidName',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam(Nas::EXTRA_PARAM_SSID),
                    ),
                )
            )
            ->add(
                'radioId',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam(Tplinkv4Fields::EXTRA_PARAM_RADIO_ID),
                    ),
                )
            )
            ->add(
                'radiusServerIp',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam(Tplinkv4Fields::EXTRA_PARAM_RADIUS_SERVER_IP),
                    ),
                )
            )
            ->add(
                'vid',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam(Tplinkv4Fields::EXTRA_PARAM_VID),
                    ),
                )
            )
            ->add(
                'target',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam(Tplinkv4Fields::EXTRA_PARAM_TARGET),
                    ),
                )
            )
            ->add(
                'targetPort',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  $nas->getExtraParam(Tplinkv4Fields::EXTRA_PARAM_TARGET_PORT),
                    ),
                )
            )
            ->add(
                'authType',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' =>  2,
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
                    'id' => 'formLoginControladora'
                ),
                'method' => 'POST',
                'username' => null,
                'password' => null,
            )
        );
    }
    public function getBlockPrefix()
    {
        return 'tp_link_v4';
    }
}