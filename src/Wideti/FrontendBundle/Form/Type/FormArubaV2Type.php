<?php
/**
 * Compatível com ArubaOS 6.x, 7.x, 8.x
 * 
 * Endpoint /cgi-bin/login
 * Metodo GET ou POST
 * Parâmetros:
 * 
 * Name            | Required |Description
 * cmd             | yes      | Value "authenticate": sent by the wireless client in order to authenticate. 
 * mac             | no       | MAC address of the wireless client connecting to the network. 
 * ip              | no       | IPv4 address of the wireless client connecting to the network. 
 * network         | no       | Network name (SSID) the wireless client is connecting to. 
 * url             | no       | Original URL the device was trying to reach when intercepted by Instant On. 
 * user            | yes      | Username to be used for authentication. 
 * password        | yes      | Password to be used for authentication. 
 * session_timeout | no       | Maximum session timeout for the authenticated wireless client.
 * 
 * Referencia:
 * https://community.arubainstanton.com/communities/community-home/digestviewer/viewthread?MID=406
 * 
 * Configurando o Aruba:
 * https://www.arubanetworks.com/techdocs/InstantWenger_Mobile/Advanced/Content/External%20Captive%20Portal.htm
 * 
 * Teste local:
 * https://dev.wspot.com.br/app_dev.php/aruba_v2?cmd=login&switchip=10.85.202.11&mac=11-11-11-11-11-12&ip=192.168.72.56&essid=_owetm_Cap-Mambo_45347825&apname=FOR_AP243&apgroup=FOR_TI&apmac=50:e4:e0:cb:14:da&url=https://dev.wspot.com.br/app_dev.php/302
 */

namespace Wideti\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FormArubaV2Type extends AbstractType
{
    use SessionAware;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $redirectUrl = $this->getRedirect302();
        $builder
            ->add(
                'cmd',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => 'authenticate',
                    ),
                )
            )
            ->add(
                'mac',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $this->session->get('mac'),
                    ),
                )
            )
            ->add(
                'network',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $this->session->get('essid'),
                    ),
                )
            )
            ->add(
                'ip',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $this->session->get('ip'),
                    ),
                )
            )
            ->add(
                'url',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $redirectUrl,
                    ),
                )
            )
            ->add(
                'user',
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
                'login',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => 'http://'.$this->session->get('switchip'),
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
     * @var Client $client
     * @return string
     */
    private function getRedirect302() {
        $client = $this->session->get("wspotClient");
        if ($client->isWhiteLabel()){
            return "http://{$client->getDomain()}/302";
        }
        return "http://{$client->getDomain()}.mambowifi.com/302";
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'aruba_v2';
    }
}
