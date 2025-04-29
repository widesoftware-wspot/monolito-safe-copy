<?php

namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\AccessPoints;

class downloadUploadFilterType extends AbstractType
{
    protected $em;
    protected $client;
    private $years = array();

    public function __construct(EntityManager $em)
    {
        $this->em       = $em;
        $current_year   = date("Y");

        for ($i = 0; $i <= 2; $i++) {
            $year = $current_year - $i;
            $this->years[$year] = $year;
        }
    }

    public function accessPointsList()
    {
        $entities = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->listAll($this->client, null, null, [
                'status' => AccessPoints::ACTIVE
            ]);

        $apGroups = ["Sem Grupo" => []];

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
        $client = $this->em->getRepository('DomainBundle:Client')->findOneBy([
            'id' => $options['attr']['client']
        ]);
        $this->client = $client;
        $builder
            ->add(
                'access_point',
                ChoiceType::class,
                array(
                    'label'     => 'Ponto de acesso',
                    'required'  => false,
                    'multiple'  => true,
                    'attr'      => array(
                        'multiple'  => 'multiple'
                    ),
                    'choices' => $this->accessPointsList()
                )
            )
            ->add(
                'year',
                ChoiceType::class,
                array(
                    'label'       => 'Ano',
                    'placeholder' => 'Escolha o ano',
                    'required'    => true,
                    'choices'     => $this->years,
                    'attr'   => array(
                        'class' => 'input-mini',
                        'style' => 'width: 130px;'
                    )
                )
            )
            ->add(
                'month',
                ChoiceType::class,
                array(
                    'label'         => 'Mês',
                    'placeholder'   => 'Escolha o mês',
                    'required'      => true,
                    'choices'       => array(
                        'Janeiro' => '1',
                        'Fevereiro' => '2',
                        'Março' => '3',
                        'Abril' => '4',
                        'Maio' => '5',
                        'Junho' => '6',
                        'Julho' => '7',
                        'Agosto' => '8',
                        'Setembro' => '9',
                        'Outubro' => '10',
                        'Novembro' => '11',
                        'Dezembro' => '12'
                    ),
                    'attr'   => array(
                        'class' => 'input-mini',
                        'style' => 'width: 130px;'
                    )
                )
            )
            ->add(
                'filtrar',
                SubmitType::class,
                array(
                    'attr' => array(
                        'class' => 'btn btn-default'
                    )
                )
            );

        $builder->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array( 'csrf_protection' => false ));
    }

    public function getBlockPrefix()
    {
        return 'downloadUploadFilter';
    }
}
