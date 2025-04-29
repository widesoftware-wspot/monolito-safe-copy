<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class DeskbeeIntegrationType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enable_deskbee_integration', CheckboxType::class, [
                'label' => 'Ativar integração',
                'data' => boolval($options['enable_deskbee_integration']->getValue()),
            ])
            ->add('deskbee_client_id', TextType::class, [
                'label' => 'Client ID',
                'required' => true,
                'data' => $options['deskbee_client_id']->getValue(),
                'attr'     => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('deskbee_client_secret', TextType::class, [
                'label' => 'Client Secret',
                'required' => true,
                'data' => $options['deskbee_client_secret']->getValue(),
                'attr'     => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('deskbee_redirect_url', TextType::class, [
                'label' => $options['deskbee_redirect_url']->getItems()->getLabel(),
                'required' => true,
                'data' => $options['deskbee_redirect_url']->getValue(),
                'attr'     => [
                    'autocomplete' => 'off'
                ],
                'constraints' => array(new Url())
            ])
            ->add('deskbee_environment', ChoiceType::class, [
                'choices' => [
                    'Sandbox' => 'dev',
                    'Produção' => 'prod',
                ],
                'label' => $options['deskbee_environment']->getItems()->getLabel(),
                'data' => $options['deskbee_environment']->getValue(),
            ])
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                    ],
                    'label' => 'Salvar'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'enable_deskbee_integration' => null,
                'deskbee_client_id' => null,
                'deskbee_client_secret' => null,
                'deskbee_redirect_url' => null,
                'deskbee_environment' => null
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'wspot_deskbee_integration';
    }
}