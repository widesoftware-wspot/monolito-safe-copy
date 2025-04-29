<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignHoursType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'startTime',
                TextType::class,
                array(
                    'required' => true,
                    'label'    => 'De',
                    'attr'     => array(
                        'class' => 'span11 mask-hour'
                    ),
                    'label_attr' => array(
                        'class' => 'control-label'
                    )
                )
            )
            ->add(
                'endTime',
                TextType::class,
                array(
                    'required' => true,
                    'label'    => 'Até',
                    'attr'     => array(
                        'class' => 'span11 mask-hour'
                    ),
                    'label_attr' => array(
                        'class' => 'control-label'
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
                'data_class' => 'Wideti\DomainBundle\Entity\CampaignHours',
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_campaign_hours';
    }
}