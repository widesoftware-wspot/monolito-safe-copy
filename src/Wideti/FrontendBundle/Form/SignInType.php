<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Wideti\DomainBundle\Form\GuestFieldType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SignInType extends AbstractType
{
    use MongoAware;
    use LoggerAware;
    use TranslatorAware;
    use CustomFieldsAware;
    use SessionAware;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;

	/**
	 * SignInType constructor.
	 * @param CacheServiceImp $cacheService
	 */
	public function __construct(CacheServiceImp $cacheService)
	{
		$this->cacheService = $cacheService;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $client = $this->getLoggedClient();
        $field  = $this->getLoginFieldCache();
        $entity = $builder->getForm()->getData();

        $builder->add('properties', GuestFieldType::class, [
            'fields'        => $field,
            'label'         => false,
            'property_path' => 'properties',
            'guest'         => $entity
        ]);

        $builder
            ->add(
                'password',
                PasswordType::class,
                [
                    'label' => 'wspot.login_page.signup_password_input',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required' => true,
                    'attr' => [
                        'class'                 => 'span12',
                        'data-rule-maxlength'   => 30,
                        'data-msg-maxlength'    => $this->translator->trans('wspot.signup_page.field_password_max_characters_required'),
                        'data-rule-required'    => 'true',
                        'data-msg-required'     => $this->translator->trans('wspot.signup_page.field_required'),
                        'data-rule-minlength'   => 6,
                        'data-msg-minlength'    => $this->translator->trans('wspot.signup_page.field_password_min_characters_required'),
                        'style' => ($client->getDomain() == 'kopclub') ? 'display:none;' : 'display:block;'
                    ]
                ]
            )
            ->add(
                'entrar',
                SubmitType::class,
                [
                    'label' => 'entrar',
                    'attr' => [
                        'class' => 'btnLogin'
                    ]
                ]
            );
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
        return 'wspot_signin';
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
