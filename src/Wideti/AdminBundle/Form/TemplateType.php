<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'required' => true,
                    'label'    => 'Nome do Template',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'filePartnerLogo',
                FileType::class,
                array(
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                )
            )
            ->add(
                'fileBackgroundImage',
                FileType::class,
                array(
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                )
            )
            ->add(
                'fileBackgroundPortraitImage',
                FileType::class,
                array(
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                )
            )
            ->add(
                'backgroundRepeat',
                HiddenType::class,
                array(
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                )
            )
            ->add(
                'backgroundPositionX',
                HiddenType::class,
                array(
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                )
            )
            ->add(
                'backgroundPositionY',
                HiddenType::class,
                array(
                    'data_class' => null,
                    'required' => false,
                    'label'    => false,
                )
            )
            ->add(
                'backgroundColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor de Fundo',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    )
                )
            )
            ->add(
                'fontColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor da Fonte',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'boxOpacity',
                CheckboxType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'loginBoxColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor de Fundo',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'loginFontColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor da Fonte',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'loginButtonColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor do Botão',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'loginButtonFontColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor da Fonte do Botão',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'boxOpacity',
                CheckboxType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'signupBoxColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor de Fundo',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'signupFontColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor da Fonte',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'signupButtonColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor do Botão',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add(
                'signupButtonFontColor',
                TextType::class,
                array(
                    'block_name' => 'color',
                    'required' => false,
                    'label'    => 'Cor da Fonte do Botão',
                    'attr'     => array(
                        'class' => 'span10',
                        'autocomplete' => 'off',
                    ),
                    'label_attr' => array(
                        'class' => 'control-label',
                    ),
                )
            )
            ->add('partnerLogo', HiddenType::class)
            ->add(
                'submit',
                SubmitType::class,
                array(
                    'label' => 'Salvar',
                    'attr'   => array(
                        'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                    ),
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Wideti\DomainBundle\Entity\Template',
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'wideti_AdminBundle_template';
    }
}
