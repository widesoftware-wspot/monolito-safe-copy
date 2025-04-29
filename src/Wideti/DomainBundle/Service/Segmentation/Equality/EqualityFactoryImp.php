<?php

namespace Wideti\DomainBundle\Service\Segmentation\Equality;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EqualityFactoryImp implements EqualityFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * EqualityFactoryImp constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get($identifier, $equality)
    {
        $service = strtolower("segmentation.datasource.{$identifier}.{$equality}");
        return $this->container->get($service);
    }
}
