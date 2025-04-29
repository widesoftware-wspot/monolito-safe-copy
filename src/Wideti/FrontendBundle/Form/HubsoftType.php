<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class HubsoftType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'username',
            TextType::class,
            [
                'required' => true,
                'label' => 'CPF'
            ]
        )
        ->add(
            'password',
            PasswordType::class,
            [
                'required' => true,
                'label' => 'Senha'
            ]);

        $builder
            ->add(
                'cadastrar',
                SubmitType::class,
                [
                    'label' => 'wspot.login_page.login_submit_input'
                ]
            );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_hubsoft_auth';
    }
}