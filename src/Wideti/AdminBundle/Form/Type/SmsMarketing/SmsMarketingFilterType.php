<?php

namespace Wideti\AdminBundle\Form\Type\SmsMarketing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Class SmsMarketingFilterType
 * @package Wideti\AdminBundle\Form\Type\SmsMarketing
 */
class SmsMarketingFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                "status",
                ChoiceType::class,
                [
                    "label"     => "Status",
                    "required"  => false,
                    "choices" => [
                        "Rascunho" => "draft",
                        "Processando" => "processing",
                        "Enviado" => "sent",
                        "Removido" => "removed",
                    ],
                    "attr" => [
                        "style" => "width: 120px;"
                    ],
                    "placeholder" => "Selecione",
                    "data" => 1
                ]
            )
            ->add(
                "filtrar",
                SubmitType::class,
                [
                    "attr" => [ "class" => "btn btn-default" ]
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(["csrf_protection" => false]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return "SmsMarketingFilterType";
    }
}
