<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\AccessPoints;

class CampaignReportFilterType extends AbstractType
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function campaignList($client)
    {
        $entities = $this->em
            ->getRepository('DomainBundle:Campaign')
            ->findByClient($client);

        $campaigns = array();

        foreach ($entities as $campaign) {
            $allCampaigns = "Selecionar todas";

            if (!in_array($allCampaigns, $campaigns)) {
                $campaigns += array($allCampaigns => array());
            }

            $campaigns[$allCampaigns] += array($campaign->getName() => $campaign->getId());
        }

        return $campaigns;
    }

    public function accessPointsList($client)
    {
        $entities = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->listAll($client, null, null, [
                'status' => AccessPoints::ACTIVE
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'campaign',
                ChoiceType::class,
                [
                    'label'         => 'Campanhas',
                    'multiple'      => true,
                    'required'      => false,
                    'attr' => [
                        'multiple'  => 'multiple',
                        'class'     => 'span7'
                    ],
                    'choices' => $this->campaignList($options['attr']['client'])
                ]
            )
            ->add(
                'date_from',
                DateType::class,
                [
                    'label'  => 'Início',
                    'required' => false,
                    'widget'   => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'attr'   => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini',
                    ]
                ]
            )
            ->add(
                'date_to',
                DateType::class,
                [
                    'label'     => 'Fim',
                    'widget'    => 'single_text',
                    'format'    => 'dd/MM/yyyy',
                    'required'  => false,
                    'attr'      => [
                        'autocomplete' => 'off',
                        'class' => 'input-mini'
                    ]
                ]
            )
            ->add(
                'filtrar',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-default'
                    ]
                ]
            );

        $builder->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array( 'csrf_protection' => false ));
    }

    public function getBlockPrefix()
    {
        return 'campaignReportFilter';
    }
}