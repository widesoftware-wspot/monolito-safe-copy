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

class FormIntelbrasType extends AbstractType
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
        $client = $this->session->get('wspotClient');

        $builder
            ->add(
                'redirect_uri',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam('redirect_uri'),
                    ),
                )
            )
            ->add(
                'continue',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $this->getRedirect302($client),
                    ),
                )
            )
            ->add(
                'user_hash',
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => array(
                        'value' => $nas->getExtraParam('user_hash'),
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

    private function getRedirect302(Client $client)
    {
        if ($client->isWhiteLabel()){
           return "http://{$client->getDomain()}/302";
        }
        return "http://{$client->getDomain()}.mambowifi.com/302";
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
        return 'intelbras';
    }
}
