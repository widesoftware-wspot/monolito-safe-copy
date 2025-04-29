<?php

namespace Wideti\DomainBundle\Service\Segmentation\Dto;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Segmentation;

class SegmentationDto
{
    public $id;
    public $title;
    public $status;
    public $filter;
    public $created;
    public $updated;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param $updated
     * @return $this
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @param Request $request
     * @return Segmentation
     */
    public static function createFromRequest(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        $segmentation = new Segmentation();
        $segmentation->setTitle($content['title']);
        $segmentation->setStatus((array_key_exists('status', $content)) ? $content['status'] : Segmentation::ACTIVE);
        $segmentation->setFilter(json_encode($content['filter']));

        return $segmentation;
    }

    /**
     * @var Segmentation $segmentation
     * @param $result
     * @return string
     */
    public static function convertEntityToDto($segmentation)
    {
        $dto = new SegmentationDto();
        $dto->setId($segmentation->getId());
        $dto->setStatus($segmentation->getStatus());
        $dto->setTitle($segmentation->getTitle());
        $dto->setFilter(json_decode($segmentation->getFilter()));
        $dto->setCreated(date('Y-m-d H:i:s', $segmentation->getCreated()->sec));
        $dto->setUpdated(date('Y-m-d H:i:s', $segmentation->getUpdated()->sec));
        return get_object_vars($dto);
    }

    public static function createFromArray(array $segmentations)
    {
        $result = [];

        if (empty($segmentations)) return $result;

        foreach ($segmentations as $segmentation) {
            $result[] = self::createFromEntity($segmentation);
        }

        return $result;
    }

    public static function createFromEntity(Segmentation $segmentation = null)
    {
        if (!$segmentation) return null;

        $dto = new SegmentationDto();

        return $dto
            ->setId($segmentation->getId())
            ->setStatus($segmentation->getStatus())
            ->setTitle($segmentation->getTitle())
            ->setFilter(json_decode($segmentation->getFilter()))
            ->setCreated($segmentation->getCreated()->format('Y-m-d H:i:s'))
            ->setUpdated($segmentation->getUpdated()->format('Y-m-d H:i:s'))
            ;
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
