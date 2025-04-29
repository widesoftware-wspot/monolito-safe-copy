<?php

namespace Wideti\AdminBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlacklistType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $submitLabel = "Bloquear";

        if($options['data']->getId()){
            $builder->add(
                'id',
                HiddenType::class,
                array(
                    'required' => true
                )
            );

            $submitLabel = "Salvar";
        }

        $builder->add(
            'macAddress',
            TextType::class,
            array(
                'required' => true,
                'label'    => 'Mac Address',
                'attr'     => array(
                    'class' => 'span10',
                ),
                'label_attr' => array(
                    'class' => 'control-label',
                ),
            )
        );

        $builder->add(
            'submit',
            SubmitType::class,
            array(
                'attr'   => array(
                    'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                ),
                'label' => $submitLabel
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'wspot_blacklist_form';
    }
}