<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignMediaVideoType extends AbstractType
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em     = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nextLabel = 'Avançar';

        $builder
            ->add(
                'video',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required'   => false,
                    'mapped'     => false,
                    'label'      => false
                ]
            )
            ->add(
                'orientation',
                HiddenType::class,
                [
                    'required'   => false,
                    'label'      => false
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'attr'   => [
                        'class' => 'btn btn-icon btn-primary glyphicons chevron-right hide',
                    ],
                    'label' => $nextLabel
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'campaign_step_media_video';
    }
}
