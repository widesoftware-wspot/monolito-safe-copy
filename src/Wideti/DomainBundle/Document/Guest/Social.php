<?php

namespace Wideti\DomainBundle\Document\Guest;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument()
 */
class Social
{
    const FACEBOOK  = 1;
    const TWITTER   = 2;
    const GOOGLE    = 3;
    const INSTAGRAM = 4;
    const OAUTH     = 5;
    const LINKEDIN  = 6;
    const HUBSOFT   = 7;
    const IXC       = 8;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index(order="asc")
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     */
    protected $type;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
