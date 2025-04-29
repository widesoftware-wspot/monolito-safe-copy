<?php

namespace Wideti\FrontendBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Validator\Constraints\DomainEmailIsValid;
use Wideti\DomainBundle\Validator\Constraints\EmailIsValid;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Form\GuestFieldType;

class ChangeUserDataType extends AbstractType
{
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Guest $entity */
        $entity = $builder->getForm()->getData();
        $field = null;

        if (strpos($options['action'], 'email') !== false) {
            $field = 'email';
        }

        if (strpos($options['action'], 'phone') !== false || strpos($options['action'], 'mobile') !== false) {
	        if (array_key_exists('phone', $entity->getProperties())) {
		        $field = 'phone';
	        }

	        if (array_key_exists('mobile', $entity->getProperties())) {
		        $field = 'mobile';
	        }

	        $builder->add("country-code-{$field}", 'hidden', [
		        'required' => false,
		        'mapped' => false
	        ]);
        }

        if (!$field) {
            $err = "guest {$entity->getMysql()} has't phone, mobile or email to edit confirmation field, data: {$options['action']}";
            throw new \InvalidArgumentException($err);
        }

        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findByIdentifier($field);

        $builder->add('properties', GuestFieldType::class, [
            'fields'        => $fields,
            'label'         => false,
            'property_path' => 'properties',
            'guest'         => $entity
        ]);

        $builder->add(
            'alterar',
            SubmitType::class,
            [
                'label' => 'wspot.change_user_data.save_button'
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
        return 'wspot_change';
    }
}
