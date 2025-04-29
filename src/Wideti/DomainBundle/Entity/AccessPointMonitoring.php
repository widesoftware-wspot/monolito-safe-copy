<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AccessPointMonitoring
 * @package Wideti\DomainBundle\Entity
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessPointMonitoringRepository")
 */
class AccessPointMonitoring
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="`schema`", type="text")
     */
    private $schema;

    /**
     * @ORM\ManyToOne(targetEntity="AccessPoints", inversedBy="monitoring", cascade={"persist"})
     * @ORM\JoinColumn(name="access_point_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $accessPoint;

    /**
     * AccessPointMonitoring constructor.
     * @param $schema
     * @param $accessPoint
     */
    public function __construct($schema, $accessPoint)
    {
        $this->schema = $schema;
        $this->accessPoint = $accessPoint;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function getPanels()
    {
        if (!empty($this->getSchema())) {
            $schema = json_decode($this->getSchema(), true);
            return $schema['dashboard']['panels'];
        }
        return [];
    }
}
