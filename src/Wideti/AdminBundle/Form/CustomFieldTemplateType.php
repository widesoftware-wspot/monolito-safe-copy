<?php
namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Entity\CustomFieldTemplate;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Form\GuestFieldType;


class CustomFieldTemplateType extends AbstractType
{
    use EntityManagerAware;
    use SessionAware;
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $namePt = null;
        $nameEs = null;
        $nameEn = null;
        
        $typeChoices = $options['fieldTypes'];
        if (isset($options['entity'])) {
            $entity = $options['entity'];
            $names = $entity->getName();
            $namePt = $names["pt_br"];
            $nameEs = $names["es"];
            $nameEn = $names["en"];
            $entityType = $entity->getType();
            $typeChoices = [$entityType => $typeChoices[$entityType]];

            $fields = $this->getMockedCustomFields($entity);
        } else {
            $fields = $this->getMockedCustomFields();
        }
        
        $existingChoices = $options['existing_choices'];
        $builder
            ->add(
                'clientDomain',
                HiddenType::class,
                [
                    'data' => $options['attr']['clientDomain'],
                    'mapped' => false
                ]
            )->add(
                'labelPt',
                TextType::class,
                [
                    'label' => 'Idioma Português {{ image_placeholder }}',
                    'mapped' => false,
                    'data' => $namePt,
                    'required' => true,
                    'attr'     => [
                        'class' => 'span14',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'labelEs',
                TextType::class,
                [
                    'label' => 'Idioma Espanhol {{ image_placeholder }}',
                    'mapped' => false,
                    'data' => $nameEs,
                    'required' => true,
                    'attr'     => [
                        'class' => 'span14',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
                'labelEn',
                TextType::class,
                [
                    'label' => 'Idioma Inglês {{ image_placeholder }}',
                    'mapped' => false,
                    'required' => true,
                    'data' => $nameEn,
                    'attr'     => [
                        'class' => 'span14',
                        'autocomplete' => 'off'
                    ],
                    'label_attr' => [
                        'class' => 'control-label'
                    ]
                ]
            )
            ->add(
            'type',
            ChoiceType::class,
            [
                'label' => 'Tipo de campo',
                'choices'  => array_flip($typeChoices),
                'multiple' => false,
                'required' => true,
                'attr'     => [
                    'class' => 'span14 input-field',
                    'autocomplete' => 'off'
                ],
                'label_attr' => [
                    'class' => 'control-label'
                ]
            ]
        )->add(
            'previewLanguage',
            ChoiceType::class,
            [
                'label' => false,
                'mapped' => false,
                'choices'  => [
                    ' Português' => 'pt_br',
                    ' Espanhol' => 'es',
                    ' Inglês' => 'en',
                ],
                'multiple' => false,
                'required' => true,
                'attr'     => [
                    'class' => 'span10',
                    'autocomplete' => 'off'
                ],
                'label_attr' => [
                    'class' => 'control-label'
                ]
            ]
        )->add(
            'submit',
            SubmitType::class,
            [
                'attr' => [
                    'class' => 'btn btn-icon btn-primary glyphicons circle_ok'
                ],
                'label' => 'Salvar'
            ]
        );

        $builder->add(
            'properties',
            GuestFieldType::class,
            [
                'fields'        => $fields,
                'mapped' =>     false,
                'label'         => false,
                'property_path' => 'properties',
            ]
        ) ->add('choices', CollectionType::class, [
            'entry_type' => CustomFieldChoiceType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'by_reference' => false,
            'label' => false,
            'mapped' => false,
            'data' => $options['existing_choices'],
        ]);
    }

    /**
     * @return mixed
     */
    private function getMockedCustomFields($entity = null)
    {
        $fields = [];
        $fieldsMocked = [
            '1'=>['id'=>'1','type'=>'text','name'=>['pt_br'=>'texto','en'=>'','es'=>''],'identifier'=>'text_pt_br','choices'=>[],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false,],
            '2'=>['id'=>'2','type'=>'date','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'date_pt_br','choices'=>[],'validations'=>[],'mask'=>['pt_br'=>'99/99/9999','en'=>'99/99/9999','es'=>'99/99/9999'],'isUnique'=>false,'isLogin'=>false],
            '3'=>['id'=>'3','type'=>'choice','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'choice_pt_br','choices'=>['pt_br'=>['Selecione'=> ''],'en'=>['test'=> ''],'es'=>['test'=> '']],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false],
            '4'=>['id'=>'4','type'=>'multiple_choice','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'multiple_choice_pt_br','choices'=>['pt_br'=>[''=> ''],'en'=>[''=> ''],'es'=>[''=> '']],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false],
            '5'=>['id'=>'1','type'=>'text','name'=>['pt_br'=>'texto','en'=>'','es'=>''],'identifier'=>'text_en','choices'=>[],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false,],
            '6'=>['id'=>'2','type'=>'date','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'date_en','choices'=>[],'validations'=>[],'mask'=>['pt_br'=>'99/99/9999','en'=>'99/99/9999','es'=>'99/99/9999'],'isUnique'=>false,'isLogin'=>false],
            '7'=>['id'=>'3','type'=>'choice','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'choice_en','choices'=>['pt_br'=>['Selecione'=> ''],'en'=>['test'=> ''],'es'=>['test'=> '']],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false],
            '8'=>['id'=>'4','type'=>'multiple_choice','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'multiple_choice_en','choices'=>['pt_br'=>[''=> ''],'en'=>[''=> ''],'es'=>[''=> '']],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false],
            '9'=>['id'=>'1','type'=>'text','name'=>['pt_br'=>'texto','en'=>'','es'=>''],'identifier'=>'text_es','choices'=>[],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false,],
            '10'=>['id'=>'2','type'=>'date','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'date_es','choices'=>[],'validations'=>[],'mask'=>['pt_br'=>'99/99/9999','en'=>'99/99/9999','es'=>'99/99/9999'],'isUnique'=>false,'isLogin'=>false],
            '11'=>['id'=>'3','type'=>'choice','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'choice_es','choices'=>['pt_br'=>['Selecione'=> ''],'en'=>['test'=> ''],'es'=>['test'=> '']],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false],
            '12'=>['id'=>'4','type'=>'multiple_choice','name'=>['pt_br'=>'','en'=>'','es'=>''],'identifier'=>'multiple_choice_es','choices'=>['pt_br'=>[''=> ''],'en'=>[''=> ''],'es'=>[''=> '']],'validations'=>[],'mask'=>[],'isUnique'=>false,'isLogin'=>false]
        ];

        $choices = ['pt_br'=>[''=> ''],'en'=>[''=> ''],'es'=>[''=> '']];

        if ($entity) {
            $entityType = $entity->getType();
            $names = $entity->getName();
            if ($entityType == "choice" || $entityType == "multiple_choice") {
                $choices = $entity->getChoices();
            } 
            $fieldsMocked = [
                '1'=> [
                    'id'=>'1',
                    'type'=>$entityType,
                    'name'=>['pt_br'=>$names["pt_br"],'en'=>$names["en"],'es'=>$names["es"]],
                    'identifier' => $entityType . '_pt_br',
                    'choices'=>$choices,
                    'validations'=>[],
                    'mask'=> $entityType == "date" ? ['pt_br'=>'99/99/9999','en'=>'99/99/9999','es'=>'99/99/9999'] : [],
                    'isUnique'=>false,
                    'isLogin'=>false
                ],
                '2'=> [
                    'id'=>'2',
                    'type'=>$entityType,
                    'name'=>['pt_br'=>$names["pt_br"],'en'=>$names["en"],'es'=>$names["es"]],
                    'identifier' => $entityType . '_en',
                    'choices'=>$choices,
                    'validations'=>[],
                    'mask'=> $entityType == "date" ? ['pt_br'=>'99/99/9999','en'=>'99/99/9999','es'=>'99/99/9999'] : [],
                    'isUnique'=>false,
                    'isLogin'=>false
                ],
                '3'=> [
                    'id'=>'3',
                    'type'=>$entityType,
                    'name'=>['pt_br'=>$names["pt_br"],'en'=>$names["en"],'es'=>$names["es"]],
                    'identifier' => $entityType . '_es',
                    'choices'=>$choices,
                    'validations'=>[],
                    'mask'=> $entityType == "date" ? ['pt_br'=>'99/99/9999','en'=>'99/99/9999','es'=>'99/99/9999'] : [],
                    'isUnique'=>false,
                    'isLogin'=>false
                ]
            ];
        }

        foreach ($fieldsMocked as $fieldMockedId => $fieldMocked) {
            $field = new Field();
            foreach ($fieldsMocked[$fieldMockedId] as $key => $value) {
                $field->__set($key, $value);
            }
		    $fields[$fieldMockedId] = $field;
        }

        return $fields;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'fieldTypes' => array(),
                'existing_choices' => [],
                'entity' => null,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'wspot_custom_field_template';
    }
}