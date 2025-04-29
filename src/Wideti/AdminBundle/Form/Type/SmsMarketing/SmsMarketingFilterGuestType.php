<?php

namespace Wideti\AdminBundle\Form\Type\SmsMarketing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class SmsMarketingFilterGuestType extends AbstractType
{
    use MongoAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $query = isset($options['attr']['query']) ? json_decode($options['attr']['query'], true) : null;

        $groups = $this->mongo->getRepository('DomainBundle:Group\Group')->getGroupsToForm();
        $groups[""] = "Todos";
        ksort($groups);

        $builder
            ->add(
                'group',
                ChoiceType::class,
                [
                    'choices'   => array_flip($groups),
                    'label'     => 'Visitantes na Regra de acesso',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => false,
                    "attr" => [
                        "style" => "width: 120px;"
                    ],
                    'data' => $query ? $query['group'] : ''
                ]
            )
            ->add(
                'ddd',
                ChoiceType::class,
                [
                    'choices'   => $this->getDDD(),
                    'label'     => 'Código de área',
                    'label_attr' => [
                        'class' => 'control-label'
                    ],
                    'required'  => false,
                    "attr" => [
                        "style" => "width: 100px;"
                    ],
                    'data' => $query ? $query['ddd'] : ''
                ]
            )
            ->add(
                'dateFrom',
                DateType::class,
                [
                    'label'     => 'Cadastros de: ',
                    'required'  => false,
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'attr'      => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini',
                        'style' => 'width: 100px;'
                    ],
                    'data' => empty($query['dateFrom']) ? null : \DateTime::createFromFormat("d/m/Y", $query['dateFrom'])
                ]
            )
            ->add(
                'dateTo',
                DateType::class,
                [
                    'label'    => 'até: ',
                    'widget'   => 'single_text',
                    'format'   => 'dd/MM/yyyy',
                    'required' => false,
                    'attr'     => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini',
                        'style' => 'width: 100px;'
                    ],
                    'data' => empty($query['dateTo']) ? null : \DateTime::createFromFormat("d/m/Y", $query['dateTo'])
                ]
            )
            ->add(
                'submit',
                ButtonType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-icon btn-primary'
                    ],
                    'label' => 'Filtrar'
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(["csrf_protection" => false]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return "wspot_sms_marketing_filter_guests";
    }

    private function getDDD()
    {
        return [
            "" => "Todos",
            "11" => "DDD 11",
            "12" => "DDD 12",
            "13" => "DDD 13",
            "14" => "DDD 14",
            "15" => "DDD 15",
            "16" => "DDD 16",
            "17" => "DDD 17",
            "18" => "DDD 18",
            "19" => "DDD 19",
            "21" => "DDD 21",
            "22" => "DDD 22",
            "24" => "DDD 24",
            "27" => "DDD 27",
            "28" => "DDD 28",
            "31" => "DDD 31",
            "32" => "DDD 32",
            "33" => "DDD 33",
            "34" => "DDD 34",
            "35" => "DDD 35",
            "37" => "DDD 37",
            "38" => "DDD 38",
            "41" => "DDD 41",
            "42" => "DDD 42",
            "43" => "DDD 43",
            "44" => "DDD 44",
            "45" => "DDD 45",
            "46" => "DDD 46",
            "47" => "DDD 47",
            "48" => "DDD 48",
            "49" => "DDD 49",
            "51" => "DDD 51",
            "53" => "DDD 53",
            "54" => "DDD 54",
            "55" => "DDD 55",
            "61" => "DDD 61",
            "62" => "DDD 62",
            "63" => "DDD 63",
            "64" => "DDD 64",
            "65" => "DDD 65",
            "66" => "DDD 66",
            "67" => "DDD 67",
            "68" => "DDD 68",
            "69" => "DDD 69",
            "71" => "DDD 71",
            "73" => "DDD 73",
            "74" => "DDD 74",
            "75" => "DDD 75",
            "77" => "DDD 77",
            "79" => "DDD 79",
            "81" => "DDD 81",
            "82" => "DDD 82",
            "83" => "DDD 83",
            "84" => "DDD 84",
            "85" => "DDD 85",
            "86" => "DDD 86",
            "87" => "DDD 87",
            "88" => "DDD 88",
            "89" => "DDD 89",
            "91" => "DDD 91",
            "92" => "DDD 92",
            "93" => "DDD 93",
            "94" => "DDD 94",
            "95" => "DDD 95",
            "96" => "DDD 96",
            "97" => "DDD 97",
            "98" => "DDD 98",
            "99" => "DDD 99",
        ];
    }
}
