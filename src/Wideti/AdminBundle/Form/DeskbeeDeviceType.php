<?php
namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\DeskbeeDevice;

class DeskbeeDeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
              'device',
              TextType::class,
              [
                'label' => false,
                'attr'     => [
                  'class' => 'span10',
                  'autocomplete' => 'off',
              ],
              ]
          );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DeskbeeDevice::class,
        ]);
    }
}