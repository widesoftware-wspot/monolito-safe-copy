<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups\Dto;

use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\AccessPointTemplateDto;

class AccessPointGroupsApiDto implements \JsonSerializable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isDefault;

    /**
     * @var AccessPointTemplateDto
     */
    private $template;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param $isDefault
     * @return $this|bool
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    /**
     * @return AccessPointTemplateDto
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param AccessPointTemplateDto $template
     * @return AccessPointGroupsApiDto
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param array $accessPointGroups
     * @return AccessPointGroupsApiDto[]
     */
    public static function createFromAccessPointGroupArray(array $accessPointGroups)
    {
        $result = [];

        if (empty($accessPointGroups)) return $result;

        foreach ($accessPointGroups as $accessPointGroup) {
            $apTemplate = $accessPointGroup->getTemplate()
                ? new AccessPointTemplateDto($accessPointGroup->getTemplate()->getId(), $accessPointGroup->getTemplate()->getName())
                : new AccessPointTemplateDto(null, null);

            $apGroupApiDto = new AccessPointGroupsApiDto();

            $apGroupApiDto
                ->setName($accessPointGroup->getGroupName())
                ->setId($accessPointGroup->getId())
                ->setIsDefault($accessPointGroup->getIsDefault())
                ->setTemplate($apTemplate);

            array_push($result, $apGroupApiDto);
        }

        return $result;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        return $properties;
    }
}