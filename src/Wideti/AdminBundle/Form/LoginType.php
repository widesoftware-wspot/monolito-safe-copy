<?php

namespace Wideti\AdminBundle\Form;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                '_username',
                EmailType::class,
                array(
                    'required' => true,
                )
            )
            ->add(
                '_password',
                PasswordType::class,
                array(
                    'required' => true,
                    'attr' => array(
                        'maxlength' => 30
                    ),
                )
            )
            ->add('captcha', CaptchaType::class, [
                'label' => 'Captcha',
                'attr' => [
                    'class' => 'input-block-level',
                ],
                'invalid_message' => 'CAPTCHA inválido, tente novamente.',
                'reload' => true,
                'as_url' => true,
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
