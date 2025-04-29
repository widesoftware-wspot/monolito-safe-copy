<?php
namespace Wideti\AdminBundle\Form\Type\Reports;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class OnlineUsersFilterType extends AbstractType
{
    use MongoAware;
    use LoggerAware;
    use TranslatorAware;
    use SessionAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'access_point',
                EntityType::class,
                array(
                    'label' => 'Ponto de acesso',
                    'required' => false,
                    'class'       => 'Wideti\DomainBundle\Entity\AccessPoints',
                    'placeholder' => 'Todos',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                            return $er->createQueryBuilder('ap')
                                ->where('ap.status = 1')
                                ->innerJoin('ap.client', 'c', 'WITH', 'c.id = :client')
                                ->setParameter('client', $options['attr']['client'])
                                ->orderBy('ap.friendlyName', 'ASC');
                    },
                )
            )
            ->add(
                'filtrar',
                SubmitType::class,
                array(
                    'attr' => array(
                        'class' => 'btn btn-default',
                    ),
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
        return 'filter';
    }

}
