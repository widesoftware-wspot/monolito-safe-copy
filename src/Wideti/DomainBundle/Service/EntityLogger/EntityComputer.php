<?php

namespace Wideti\DomainBundle\Service\EntityLogger;

use Wideti\DomainBundle\Service\EntityLogger\Computers\ComputerInterface;

class EntityComputer
{
    /**
     * @var ComputerInterface
     */
    protected $computer;

    public function __construct($entity, array $changes)
    {
        $namespace      = explode("\\", get_class($entity));
        $class          = end($namespace);
        $computerClass  = "Wideti\\DomainBundle\\Service\\EntityLogger\\Computers\\" . $class;

        $this->computer = new $computerClass($entity, $changes);
    }

    public function setUow($uow)
    {
        $this->computer->setUow($uow);
    }

    public function getComputedChanges()
    {
        return $this->computer->compute();
    }

    public function getComputer()
    {
        return $this->computer;
    }
}
