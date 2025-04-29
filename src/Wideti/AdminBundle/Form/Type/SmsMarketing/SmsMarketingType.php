<?php

namespace Wideti\AdminBundle\Form\Type\SmsMarketing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmsMarketingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'lotNumber',
                HiddenType::class,
                [
                    'required' => false,
                    'data' => $this->generateLotNumber($options["attr"]["clientId"])
                ]
            )
            ->add(
                'query',
                HiddenType::class,
                [
                    'required' => true
                ]
            )
            ->add(
                'totalSms',
                HiddenType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'enableSmsLink',
                CheckboxType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'data' => false
                ]
            )
            ->add(
                'urlShortnedType',
                HiddenType::class,
                [
                    'required' => false
                ]
            )
            ->add(
                'urlShortned',
                HiddenType::class,
                [
                    'required' => false
                ]
            )
            ->add(
                'urlShortnedHash',
                HiddenType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'message',
                TextareaType::class,
                [
                    'label'    => 'Mensagem',
                    'attr'     => [
                        'class' => 'span5',
                        'rows' => 3,
                        'maxlength' => 160,
                        'style' => 'resize: none; border-color: #ddd; width: 97%;'
                    ],
                    'label_attr' => [
                        'class' => 'control-label',
                    ],
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Salvar',
                    'attr'   => [
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                    ]
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing',
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_smsMarketing';
    }

    private function generateLotNumber($clientId)
    {
        $timestamp = (new \DateTime())->getTimestamp();
        $str = "#{$clientId}#{$timestamp}";
        return strtoupper(substr(md5($str), 0, 12));
    }
}
