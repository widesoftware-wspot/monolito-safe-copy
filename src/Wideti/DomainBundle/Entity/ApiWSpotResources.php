<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="api_wspot_resources")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ApiWSpotResourcesRepository")
 */
class ApiWSpotResources
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ApiWSpot", inversedBy="resources")
     * @ORM\JoinColumn(name="api_wspot_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $api;

    /**
     * @ORM\Column(name="resource", type="string", length=55)
     */
    private $resource;

    /**
     * @ORM\Column(name="method", type="string", length=55)
     */
    private $method;

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
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param mixed $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param mixed $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    public static function getResources()
    {
        return [
            'Acessos de visitantes'         => 'accounting',
            'Grupos de Pontos de Acesso'    => 'access_point_groups',
            'Pontos de Acesso'              => 'access_points',
            'Visitantes'                    => 'guest',
            'Segmentação'                   => 'segmentation'
        ];
    }
}
