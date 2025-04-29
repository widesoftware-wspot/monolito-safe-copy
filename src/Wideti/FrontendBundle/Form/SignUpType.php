<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Wideti\DomainBundle\Form\GuestFieldType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Helpers\FieldsHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SignUpType extends AbstractType
{
    use MongoAware;
    use LoggerAware;
    use TranslatorAware;
    use SessionAware;

    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * SignUpType constructor.
     * @param CacheServiceImp $cacheService
     */
    public function __construct(CacheServiceImp $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $client = $this->getLoggedClient();
        $fields = $this->getCustomFieldsCache($options['apGroupId']);

        foreach ($fields as $key => $field) {
            if ($field->getIdentifier() === 'mac_address') {
                unset($fields[$key]);
            }
        }
        $entity = $builder->getForm()->getData();

        $builder
            ->add(
                'emailValidate',
                HiddenType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'data' => 'true'
                ]
            );

        if (in_array('authorize_email', $options['attr'])) {
            $builder
                ->add(
                    'authorizeEmail',
                    CheckboxType::class,
                    [
                        'attr' => ['checked' => true],
                        'required' => false
                    ]
                );
        }

        if ($client->isEnablePasswordAuthentication()){
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
                                'data-msg-minlength' => $this->translator->trans('wspot.signup_page.field_password_min_characters_required'),
                                'style' => ($client->getDomain() == 'kopclub') ? 'display:none;' : 'display:block;'
                            ],
                            'label_attr' => ['class' => 'control-label']
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
                                'data-rule-equalTo' => '#wspot_signup_password_first',
                                'data-msg-equalTo' => $this->translator->trans('wspot.signup_page.field_password_equalTo_required'),
                                'style' => ($client->getDomain() == 'kopclub') ? 'display:none;' : 'display:block;'
                            ],
                            'label_attr' => ['class' => 'control-label']
                        ]
                    ]
                );
        }


        $builder->add('properties', GuestFieldType::class, [
            'fields' => $fields,
            'label' => false,
            'property_path' => 'properties',
            'guest' => $entity
        ]);

        if (FieldsHelper::existsFields($fields, ['phone', 'mobile'])) {
            $builder->add('country-code-phone', HiddenType::class, [
                'required' => false,
                'mapped' => false
            ]);

            $builder->add('country-code-mobile', HiddenType::class, [
                'required' => false,
                'mapped' => false
            ]);
        }

        $builder->add('submit', SubmitType::class);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => 'Wideti\\DomainBundle\\Document\\Guest\\Guest',
            'validation_groups' => ['signUp'],
            'apGroupId' => [''],
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_signup';
    }

    /**
     * @return Field[]
     */
    private function getCustomFields($apGroupId)
    {
        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields($apGroupId);

        $fields = $fields->toArray();

        return $fields;
    }

    /**
     * @return Field[]
     */
    private function getCustomFieldsCache($apGroupId)
    {

        $client = $this->getLoggedClient();
        foreach ($client->getModules() as $module) {
            if ($module->getShortCode() == 'age_restriction'){
                return $this->getCustomFields($apGroupId);
            }
        }
        try {
            if (!$this->cacheService->isActive()) {
                $fields = $this->getCustomFields($apGroupId);
            } elseif ($this->cacheService->exists(CacheServiceImp::CUSTOM_FIELDS) !== 1) {
                $fields = $this->getCustomFields($apGroupId);
                $this->cacheService->set(CacheServiceImp::CUSTOM_FIELDS, $fields, CacheServiceImp::TTL_CUSTOM_FIELDS);
            } else {
                $fields = $this->cacheService->get(CacheServiceImp::CUSTOM_FIELDS);
            }
        } catch (\Exception $e) {
            $fields = $this->getCustomFields($apGroupId);
        }

        $filteredFields = array_filter($fields, function ($field) {
            return $field->getOnAccess() == 1 || $field->getOnAccess() == null;
        });
        return $filteredFields;
    }
}
