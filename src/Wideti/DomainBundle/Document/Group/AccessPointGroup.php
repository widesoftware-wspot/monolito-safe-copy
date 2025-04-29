<?php

namespace Wideti\DomainBundle\Document\Group;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument()
 */
class AccessPointGroup
{
    /**
     * @ODM\Id()
     */
    private $id;

    /**
     * @ODM\Field(type="integer")
     */
    private $mysqlId;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getMysqlId()
    {
        return $this->mysqlId;
    }

    /**
     * @param mixed $mysqlId
     */
    public function setMysqlId($mysqlId)
    {
        $this->mysqlId = $mysqlId;
    }
}