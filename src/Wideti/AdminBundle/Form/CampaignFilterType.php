<?php

namespace Wideti\AdminBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Helpers\FieldsHelper;

/**
 * Class CampaignFilterType
 * @package Wideti\AdminBundle\Form
 */
class CampaignFilterType extends AbstractType
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * CampaignFilterType constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $client
     * @return array
     */
    public function accessPointsList($client)
    {
        $entities = $this->entityManager->getRepository("DomainBundle:AccessPoints")
            ->listAll($client, null, null, [
                "status" => AccessPoints::ACTIVE
            ]);

        $apGroups = [
            "Sem Grupo" => []
        ];

        foreach ($entities as $ap) {
            $group = "Sem Grupo";

            if ($ap->getGroup()) {
                $group = $ap->getGroup()->getGroupName();
            }

            if (!isset($apGroups[$group])) {
                $apGroups[$group] = [];
            }

            $apGroups[$group][$ap->getFriendlyName()] = $ap->getId();
        }

        return $apGroups;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                "start_date",
                FieldsHelper::fieldTypesMap["date"],
                [
                    "label"  => "De",
                    "label_attr" => [
                        "class" => "filterRange"
                    ],
                    "required"  => false,
                    "widget"    => "single_text",
                    "format"    => "dd/MM/yyyy",
                    "attr"      => [
                        'autocomplete' => 'off',
                        "class" => "input-mini"
                    ],
                ]
            )
            ->add(
                "end_date",
                FieldsHelper::fieldTypesMap["date"],
                [
                    "label"  => "até",
                    "label_attr" => [
                        "class" => "filterRange"
                    ],
                    "required"  => false,
                    "widget"    => "single_text",
                    "format"    => "dd/MM/yyyy",
                    "attr"      => [
                        'autocomplete' => 'off',
                        "class" => "input-mini"
                    ],
                ]
            )
            ->add(
                "name",
                FieldsHelper::fieldTypesMap["text"],
                [
                    "label"     => "Campanha",
                    "required"  => false,
                    "attr" => [
                        "style" => "width: 120px;"
                    ],
                ]
            )
            ->add(
                "status",
                FieldsHelper::fieldTypesMap["choice"],
                [
                    "label"     => "Status",
                    "required"  => false,
                    "choices" => [
                        "Rascunho" => 0,
                        "Ativa" => 1,
                        "Inativa" => 2,
                        "Expirada" => 3,
                        "Todos" => "all"
                    ],
                    "attr" => [
                        "style" => "width: 70px;"
                    ],
                    "placeholder" => false,
                    "data" => 1
                ]
            )
            ->add(
                "access_points",
                FieldsHelper::fieldTypesMap["choice"],
                [
                    "label"         => "Ponto de Acesso",
                    "multiple"      => true,
                    "required"      => false,
                    "attr" => [
                        "multiple"  => "multiple",
                        "class"     => "",
                        "style"     => "width: 140px;"
                    ],
                    "choices" => $this->accessPointsList($options["attr"]["client"])
                ]
            )
            ->add(
                "type",
                FieldsHelper::fieldTypesMap["choice"],
                [
                    "label"     => "Tipo",
                    "required"  => false,
                    "attr" => [
                        "style" => "width: 80px;",
                    ],
                    "choices" => [
                        "Ambos" => "",
                        "Pré-Login" => Campaign::STEP_PRE_LOGIN,
                        "Pós-Login" => Campaign::STEP_POS_LOGIN 
                    ],
                    "placeholder" => "Ambos"
                ]
            )
            ->add(
                "filtrar",
                FieldsHelper::fieldTypesMap["submit"],
                [
                    "attr" => [ "class" => "btn btn-default" ]
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
        return "CampaignFilterType";
    }
}
