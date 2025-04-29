<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class TimeRangeType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('from', TextType::class, [
                'required' => true,
                'label' => 'De',
                'attr' => ['class' => 'span11 mask-hour from'],
                'label_attr' => ['class' => 'control-label'],
            ])
            ->add('to', TextType::class, [
                'required' => true,
                'label' => 'Até',
                'attr' => ['class' => 'span11 mask-hour to'],
                'label_attr' => ['class' => 'control-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'max_items' => null, 
        ]);
    }

    public function getBlockPrefix()
    {
        return 'wspot_time_range';
    }
}
