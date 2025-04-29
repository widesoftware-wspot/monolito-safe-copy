<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Validator\Constraints\DomainEmailIsValid;
use Wideti\DomainBundle\Validator\Constraints\EmailIsValid;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Form\GuestFieldType;

class SocialMediaRegistrationType extends AbstractType
{
    use MongoAware;
    use TranslatorAware;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * SocialMediaRegistrationType constructor.
     * @param CacheServiceImp $cacheService
     */
    public function __construct(CacheServiceImp $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = $this->getCustomFieldsCache();
        $entity = $builder->getForm()->getData();

        $builder->add('properties', GuestFieldType::class, [
            'fields'        => $fields,
            'label'         => false,
            'property_path' => 'properties',
            'guest'         => $entity
        ]);

        if ($options['authorize_email']) {
            $builder
                ->add(
                    'authorizeEmail',
                    CheckboxType::class,
                    [
                        'attr'      => ['checked' => true],
                        'required'  => false
                    ]
                );
        }

        $builder
            ->add(
                'emailValidate',
                HiddenType::class,
                [
                    'required'  => false,
                    'mapped'    => false,
                    'data'      => 'true'
                ]
            )
        ;

        $builder
            ->add(
                'cadastrar',
                SubmitType::class,
                [
                    'label' => 'wspot.login_page.signup_submit_input'
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'fields'                => [],
                'get_user_email'        => false,
                'data_class'            => 'Wideti\DomainBundle\Document\Guest\Guest',
                'display_email_field'   => false,
                'authorize_email'       => false,
                'validation_groups'     => ['signUp']
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'social_media_registration';
    }

    /**
     * @return mixed
     */
    private function getCustomFields()
    {
        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields();

        $fields = $fields->toArray();

        usort($fields, function ($field) {
            return ($field->getIdentifier() == 'email') ? -1 : 1;
        });

        return $fields;
    }

    /**
     * @return mixed
     */
    private function getCustomFieldsCache()
    {
        try {
            if (!$this->cacheService->isActive()) {
                $fields = $this->getCustomFields();
            } elseif ($this->cacheService->exists(CacheServiceImp::CUSTOM_FIELDS) !== 1) {
                $fields = $this->getCustomFields();
                $this->cacheService->set(CacheServiceImp::CUSTOM_FIELDS, $fields, CacheServiceImp::TTL_CUSTOM_FIELDS);
            } else {
                $fields = $this->cacheService->get(CacheServiceImp::CUSTOM_FIELDS);
            }
        } catch (\Exception $e) {
            $fields = $this->getCustomFields();
        }
        return $fields;
    }
}
