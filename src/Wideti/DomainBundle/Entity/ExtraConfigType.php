<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="extra_config_type")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ExtraConfigTypeRepository")
 */
class ExtraConfigType
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="config_name", type="string", length=100, nullable=false)
     */
    private $configType;
}