<?php

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\UnifinewFields;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormUnifinewType extends AbstractType
{
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var Client $client
         */
        $client = $this->session->get("wspotClient");

        /**
         * @var Nas $nas
         */
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

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
                'password_policy',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $options['password'],
                    ),
                )
            )
            ->add(
                'guest_mac_address',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getGuestDeviceMacAddress(),
                    ),
                )
            )
            ->add(
                'authorize_error_url',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(UnifinewFields::AUTHORIZE_ERROR_URL),
                    ),
                )
            )
            ->add(
                'unifi_site',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(UnifinewFields::SITE_KEY),
                    ),
                )
            )
            ->add(
                'login_url',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(UnifinewFields::LOGIN_URL_KEY),
                    ),
                )
            )
            ->add(
                'ap_mac_address',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getAccessPointMacAddress(),
                    ),
                )
            )
            ->add(
                'redirect_url',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $this->getRedirect302($client),
                    ),
                )
            )
            ->add(
                'ap_ssid',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam(Nas::EXTRA_PARAM_SSID),
                    ),
                )
            )
            ->add(
                'panel_domain',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $client->getDomain(),
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


    private function getRedirect302(Client $client)
    {
        if ($client->isWhiteLabel()){
            return "{$client->getDomain()}/302";
        }
        return "{$client->getDomain()}.mambowifi.com/302";
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'unifinew';
    }
}
