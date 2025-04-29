<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Wideti\DomainBundle\Validator\Constraints\AuthCode;

class SignUpConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'code',
                TextType::class,
                [
                    'attr' => [
                        "max_length" => 4
                    ],
                    'required'      => true,
                    'mapped'        => false,
                    'label'         => false,
                    'constraints'   => [
                        new AuthCode($options['data']),
                        new Length(array('min' => 4))
                    ]
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'wspot.confirmation.confirm_button'
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'confirmation';
    }
}
