<?php

namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

trait ComputerAware
{
    /**
     * @var
     */
    protected $entity;
    /**
     * @var array
     */
    protected $changes;

    public function getEntity()
    {
        return $this->entity;
    }

    public function getChanges()
    {
        return $this->changes;
    }
}
