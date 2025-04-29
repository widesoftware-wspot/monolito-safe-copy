<?php

namespace Wideti\DomainBundle\Service\CustomFields\Dto;

class FieldsViewDto implements \JsonSerializable
{
    private $templates;
    private $selecteds;
    private $allTemplates;

    /**
     * FieldsViewDto constructor.
     * @param array $templates
     * @param array $selected
     */
    public function __construct($templates = [], $selecteds = [], $allTemplates = [])
    {
        $this->templates = $templates;
        $this->selecteds = $selecteds;
        $this->allTemplates = $allTemplates;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param array $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

    /**
     * @return array
     */
    public function getSelecteds()
    {
        return $this->selecteds;
    }

    /**
     * @param array $selecteds
     */
    public function setSelecteds($selecteds)
    {
        $this->selecteds = $selecteds;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
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
