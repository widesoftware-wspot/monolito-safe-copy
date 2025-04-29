<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="radgroupreply", indexes={@ORM\Index(name="groupname", columns={"groupname"})})
 * @ORM\Entity()
 */
class Radgroupreply
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="groupname", type="string", length=64, nullable=false)
     */
    private $groupname;

    /**
     * @ORM\Column(name="atrribute", type="string", length=64, nullable=false)
     */
    private $attribute;

    /**
     * @ORM\Column(name="op", type="string", length=2, nullable=false, options={"fixed" = true})
     */
    private $op;

    /**
     * @ORM\Column(name="value", type="string", length=253, nullable=false)
     */
    private $value;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getGroupname()
    {
        return $this->groupname;
    }

    public function setGroupname($groupname)
    {
        $this->groupname = $groupname;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    public function getOp()
    {
        return $this->op;
    }

    public function setOp($op)
    {
        $this->op = $op;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
