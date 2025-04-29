<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignMediaImage;

class CampaignMediaImageType extends AbstractType
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
                'fullSize',
                HiddenType::class,
                [
                    'required' => false,
                    'data' => '0', // Define o valor padrão como false
                    'label' => false, // Não exibe o label
                ]
            )
            ->add(
                'exhibitionTime',
                TextType::class,
                [
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'class' => 'span1',
                        'maxlength' => 2,
                        'value' => $options['attr']['exhibitionTime']
                    ]
                ]
            )
            ->add(
                'imageDesktop',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'value' => $options['attr']['imageDesktop']
                    ]
                ]
            )
            ->add(
                'imageDesktop2',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'value' => $options['attr']['imageDesktop2']
                    ]
                ]
            )
            ->add(
                'imageDesktop3',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'value' => $options['attr']['imageDesktop3']
                    ]
                ]
            )
            ->add(
                'imageMobile',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'value' => $options['attr']['imageMobile']
                    ]
                ]
            )
            ->add(
                'imageMobile2',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'value' => $options['attr']['imageMobile2']
                    ]
                ]
            )
            ->add(
                'imageMobile3',
                HiddenType::class,
                [
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                    'attr'     => [
                        'value' => $options['attr']['imageMobile3']
                    ]
                ]
            )
            ->add(
                'imageHorizontal', // Novo campo checkbox
                CheckboxType::class,
                [
                    'required' => false,
                    'mapped' => false, // Campo não será mapeado para a entidade
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
        $resolver->setDefaults(
            [
                'data_class'         => CampaignMediaImage::class,
                'csrf_protection'    => false,
                'cascade_validation' => true,
                'validation_groups'  => function (FormInterface $form) {
                    $data = $form->getData();

                // Adiciona logs para depuração
                    $return = ['default'];

                    if (!$data->getExhibitionTime()) {
                        array_push($return, 'exhibitionTimeNotBlank');
                    }

                    if (!$data->getImageDesktop() || !$data->getImageMobile()) {
                        array_push($return, 'imageNotBlank');
                    }

                    return $return;
                }
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'campaign_step_media_image';
    }
}
