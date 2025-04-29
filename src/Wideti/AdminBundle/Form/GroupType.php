<?php

namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\ConfigurationValue;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Validator\UnboundFields\IsIntegerIfControlIsTrue;
use Wideti\DomainBundle\Validator\UnboundFields\IsValidDateTimeIfControlIsTrue;
use Wideti\DomainBundle\Validator\UnboundFields\IsValidPeriodIfControlIsTrue;
use Wideti\DomainBundle\Validator\UnboundFields\NotBlankIfControlFieldIsTrue;
use Wideti\DomainBundle\Helpers\FieldsHelper;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $submitLabel = "Adicionar";

        /**
         * @var Group $group
         */
        $group = $options['data']['entity'];
        $configurations = $options['data']['configurations'];
        $disabledNameGroup = $options['data']['entity']->getDefault();
        $messageGroup = $disabledNameGroup ? 'Essa regra é padrão do sistema, não pode ter o nome alterado.' : '';

        if ($group->getId()) {
            $builder->add(
                'id',
                HiddenType::class,
                [
                    'required' => true,
                    'data' => $group->getId()
                ]
            );

            $submitLabel = "Salvar alterações";
        }

        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required'  => true,
                    'label'     => 'Nome da regra',
                    'disabled' => $disabledNameGroup,
                    'attr'      => [
                        'class' => 'span10',
                        'title'     => $messageGroup,
                    ],
                    'label_attr' => [
                        'class' => 'control-label',
                    ],
                    'data' => $group->getName()
                ]
            );

        /**
         * @var Configuration $configuration
         */
        foreach ($configurations as $configuration) {
            $configurationValues = $configuration->getConfigurationValues();

            /**
             * @var ConfigurationValue $value
             */
            foreach ($configurationValues as $value) {
                $params = [
                    'required' => false,
                    'label'    => $value->getLabel()
                ];

                $checkBoxFields = ['enable_block_per_time', 'enable_validity_access', 'enable_bandwidth'];
                if (in_array($value->getKey(), $checkBoxFields)) {
                    $params['data'] = $value->getValue() ? true : false;
                } else {
                    $params['data'] = $value->getValue();
                }


                if (!empty($value->getParams())) {
                    foreach ($value->getParams() as $key => $v) {
                        $params[$key] = $v;
                    }
                }

                $builder->add(
                    $value->getKey(),
                    FieldsHelper::fieldTypesMap[$value->getType()],
                    $params
                );
            }
        }

        $this->createUnboundValidations($builder);

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'attr'   => [
                    'class' => 'btn btn-icon btn-primary glyphicons circle_ok',
                ],
                'label' => $submitLabel
            ]
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'wspot_group_form';
    }

    /**
     * @param FormBuilderInterface $builder
     */
    private function createUnboundValidations(FormBuilderInterface $builder)
    {
        //Block per time validations
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new NotBlankIfControlFieldIsTrue('block_per_time_time', 'enable_block_per_time', 'Não pode ser branco')
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new NotBlankIfControlFieldIsTrue('block_per_time_period', 'enable_block_per_time', 'Não pode ser branco')
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new IsValidPeriodIfControlIsTrue('block_per_time_time', 'enable_block_per_time', 'Tempo inválido')
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new IsValidPeriodIfControlIsTrue('block_per_time_period', 'enable_block_per_time', 'Período inválido')
        );

        //Access validity validations
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new NotBlankIfControlFieldIsTrue(
                'validity_access_date_limit',
                'enable_validity_access',
                'Não pode ser branco'
            )
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new IsValidDateTimeIfControlIsTrue(
                'validity_access_date_limit',
                'enable_validity_access',
                'A data deve ser válida e maior que a data e horário atual'
            )
        );

        //Bandwidth validations
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new NotBlankIfControlFieldIsTrue('bandwidth_download_limit', 'enable_bandwidth', 'Não pode ser branco')
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new NotBlankIfControlFieldIsTrue('bandwidth_upload_limit', 'enable_bandwidth', 'Não pode ser branco')
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new IsIntegerIfControlIsTrue(
                'bandwidth_download_limit',
                'enable_bandwidth',
                'Campo deve ser um número inteiro'
            )
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            new IsIntegerIfControlIsTrue(
                'bandwidth_upload_limit',
                'enable_bandwidth',
                'Campo deve ser um número inteiro'
            )
        );
    }
}
