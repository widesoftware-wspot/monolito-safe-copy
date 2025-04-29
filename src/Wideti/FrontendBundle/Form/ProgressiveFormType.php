<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
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

class ProgressiveFormType extends AbstractType
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
        $fields = $options['fields'];
        $entity = $builder->getForm()->getData();

        $builder->add('properties', GuestFieldType::class, [
            'fields'        => $fields,
            'label'         => false,
            'property_path' => 'properties',
            'guest'         => $entity
        ]);

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
                    'label' => 'wspot.login_page.login_submit_input'
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
                'data_class'            => 'Wideti\DomainBundle\Document\Guest\Guest'
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'progressive_form';
    }
}
