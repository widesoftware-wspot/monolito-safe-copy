<?php

namespace Wideti\PanelBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Wideti\DomainBundle\Entity\SmsGateway;

class SmsGatewayType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'gateway',
                ChoiceType::class,
                [
                    'label' => 'Serviço de envio',
                    'choices'   => [
                        'wavy'   => ucfirst(SmsGateway::WAVY),
                        'twilio' => ucfirst(SmsGateway::TWILIO)
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => true,
                    'attr' => [
                        'class' => 'span6'
                    ]
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr' => ['class' => 'btn btn-icon btn-primary glyphicons circle_ok'],
                    'label' => 'Editar'
                ]
            )
        ;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_panelbundle_smsgateway';
    }
}
