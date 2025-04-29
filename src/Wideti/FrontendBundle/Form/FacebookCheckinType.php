<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Wideti\DomainBundle\Service\Social\Facebook\FacebookHelper;

class FacebookCheckinType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $publishPermission = FacebookHelper::hasPermission($options['data'], FacebookHelper::PERMISSION_PUBLISH_ACTION);

        if ($publishPermission) {
            $builder
                ->add(
                    'authorize_checkin',
                    CheckboxType::class,
                    [
                        'required' => false,
                        'label' => 'wspot.login_page.signup_authorized_checkin',
                        'attr' => [
                            'checked' => 'checked'
                        ],
                        'mapped' => false
                    ]
                );

        }

        $builder
            ->add(
                'submit',
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
        return 'wspot_facebook_checkin_form';
    }
}
