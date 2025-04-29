<?php

namespace Wideti\AdminBundle\Form\Type\AccessPoints;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccessPointsImportType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'fileUpload',
                FileType::class,
                [
                    'data_class' => null,
                    'required'   => false,
                    'label'      => false,
                    'attr' => [
                        'style' => 'width: 400px;'
                    ]
                ]
            )
            ->add(
                'import',
                SubmitType::class,
                [
                    'label' => 'Importar',
                    'attr' => [
                        'class' => 'btn btn-primary'
                    ]
                ]
            );

        $builder->setMethod('POST');
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wspot_access_point_import';
    }
}
