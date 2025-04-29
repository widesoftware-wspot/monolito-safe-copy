<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Document\CustomFields\Fields\FieldFactory;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Form\GuestFieldType;

class ForgotPasswordType extends AbstractType
{
    use MongoAware;
    use TranslatorAware;
    use CustomFieldsAware;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * ForgotPasswordType constructor.
     * @param CacheServiceImp $cacheService
     */
    public function __construct(CacheServiceImp $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['attr']['step'] == 'one') {
            $field = $this->getLoginFieldCache();
            $entity = $builder->getForm()->getData();

            $builder
                ->add(
                    'properties',
                    GuestFieldType::class,
                    [
                        'fields' => $field,
                        'label' => false,
                        'property_path' => 'properties',
                        'guest' => $entity
                    ]
                )
                ->add(
                    'submit',
                    SubmitType::class,
                    [
                        'label' => 'continuar'
                    ]
                );
        } else if ($options['attr']['step'] == 'two') {
            $properties = explode(',', $options['attr']['properties']);

            $fields = $this->mongo
                ->getRepository('DomainBundle:CustomFields\Field')
                ->getRandomFields($properties)
            ;

            $fields = $fields->toArray();
            $entity = $builder->getForm()->getData();

            $builder->add(
                'properties',
                GuestFieldType::class,
                [
                    'fields' => $fields,
                    'label' => false,
                    'property_path' => 'properties',
                    'guest' => $entity,
                    'data' => null
                ]
            );

            $builder
                ->add(
                    'password',
                    RepeatedType::class,
                    [
                        'type' => PasswordType::class,
                        'required' => true,
                        'invalid_message' => $this->translator->trans(
                            'wspot.login_page.signup_password_must_match_input'
                        ),

                        'first_options' => [
                            'label' => $this->translator->trans(
                                'wspot.login_page.signup_password_input'
                            ),
                            'attr' => [
                                'class' => 'span4',
                                'maxlength' => 30,
                                'data-rule-maxlength' => 30,
                                'data-msg-maxlength' => $this->translator->trans('wspot.signup_page.field_password_max_characters_required'),
                                'data-rule-required' => 'true',
                                'data-msg-required' => $this->translator->trans('wspot.signup_page.field_required'),
                                'data-rule-minlength' => 6,
                                'data-msg-minlength' => $this->translator->trans('wspot.signup_page.field_password_min_characters_required')
                            ],
                            'label_attr' => ['class' => 'control-label'],
                        ],

                        'second_options' => [
                            'label' => $this->translator->trans(
                                'wspot.login_page.signup_confirm_password_input'
                            ),
                            'attr' => [
                                'class' => 'span4',
                                'maxlength' => 30,
                                'data-rule-maxlength' => 30,
                                'data-msg-maxlength' => $this->translator->trans('wspot.signup_page.field_password_max_characters_required'),
                                'data-rule-required' => 'true',
                                'data-msg-required' => $this->translator->trans('wspot.signup_page.field_required'),
                                'data-rule-minlength' => 6,
                                'data-msg-minlength' => $this->translator->trans('wspot.signup_page.field_password_min_characters_required'),
                                'data-rule-equalTo' => '#frontend_recovery_password_password_first',
                                'data-msg-equalTo' => $this->translator->trans('wspot.signup_page.field_password_equalTo_required')
                            ],
                            'label_attr' => ['class' => 'control-label']
                        ]
                    ]
                )
                ->add(
                    'submit',
                    SubmitType::class,
                    [
                        'label' => 'continuar'
                    ]
                );
        } else if ($options['attr']['step'] == 'pwd-only') {

            $builder
                ->add(
                    'password',
                    RepeatedType::class,
                    [
                        'type' => PasswordType::class,
                        'required' => true,
                        'invalid_message' => $this->translator->trans(
                            'wspot.login_page.signup_password_must_match_input'
                        ),

                        'first_options' => [
                            'label' => $this->translator->trans(
                                'wspot.login_page.signup_password_input'
                            ),
                            'attr' => [
                                'class' => 'span4',
                                'maxlength' => 30,
                                'data-rule-maxlength' => 30,
                                'data-msg-maxlength' => $this->translator->trans('wspot.signup_page.field_password_max_characters_required'),
                                'data-rule-required' => 'true',
                                'data-msg-required' => $this->translator->trans('wspot.signup_page.field_required'),
                                'data-rule-minlength' => 6,
                                'data-msg-minlength' => $this->translator->trans('wspot.signup_page.field_password_min_characters_required')
                            ],
                            'label_attr' => ['class' => 'control-label'],
                        ],

                        'second_options' => [
                            'label' => $this->translator->trans(
                                'wspot.login_page.signup_confirm_password_input'
                            ),
                            'attr' => [
                                'class' => 'span4',
                                'maxlength' => 30,
                                'data-rule-maxlength' => 30,
                                'data-msg-maxlength' => $this->translator->trans('wspot.signup_page.field_password_max_characters_required'),
                                'data-rule-required' => 'true',
                                'data-msg-required' => $this->translator->trans('wspot.signup_page.field_required'),
                                'data-rule-minlength' => 6,
                                'data-msg-minlength' => $this->translator->trans('wspot.signup_page.field_password_min_characters_required'),
                                'data-rule-equalTo' => '#frontend_recovery_password_password_first',
                                'data-msg-equalTo' => $this->translator->trans('wspot.signup_page.field_password_equalTo_required')
                            ],
                            'label_attr' => ['class' => 'control-label']
                        ]
                    ]
                )
                ->add(
                    'submit',
                    SubmitType::class,
                    [
                        'label' => 'continuar'
                    ]
                );
        } else if ($options['attr']['step'] == 'forget-password-choice') {
            $field = $this->getLoginFieldCache();
            $entity = $builder->getForm()->getData();

            $builder
                ->add(
                    'hidden',
                    'forget_password_choice',
                    []
                    )
                ->add(
                    'submit',
                    SubmitType::class,
                    []);
        } else if ($options['attr']['step'] == 'email') {
            $field = $this->getLoginFieldCache();
            $entity = $builder->getForm()->getData();

            $builder();
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => "Wideti\\DomainBundle\\Document\\Guest\\Guest"
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'frontend_recovery_password';
    }

    /**
     * @return mixed
     */
    private function getLoginField()
    {
        return $this->customFieldsService->getLoginField();
    }

    /**
     * @return mixed
     */
    private function getLoginFieldCache()
    {
        try {
            if (!$this->cacheService->isActive()) {
                $fields = $this->getLoginField();
            } elseif ($this->cacheService->exists(CacheServiceImp::LOGIN_FIELD) !== 1) {
                $fields = $this->getLoginField();
                $this->cacheService->set(CacheServiceImp::LOGIN_FIELD, $fields, CacheServiceImp::TTL_CUSTOM_FIELDS);
            } else {
                $fields = $this->cacheService->get(CacheServiceImp::LOGIN_FIELD);
            }
        } catch (\Exception $e) {
            $fields = $this->getLoginField();
        }
        return $fields;
    }
}
