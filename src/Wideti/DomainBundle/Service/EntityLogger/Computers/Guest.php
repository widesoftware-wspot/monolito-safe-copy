<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

class Guest implements ComputerInterface
{
    use ComputerAware;

    public function __construct(\Wideti\DomainBundle\Document\Guest\Guest $entity, array $changes)
    {
        $this->entity  = $entity;
        $this->changes = $changes;
    }

    public function compute()
    {
        if (isset($this->changes['changes']['lastAccess'])) {
            unset($this->changes['changes']['lastAccess']);
        }

        if (isset($this->changes['changes']['returning'])) {
            unset($this->changes['changes']['returning']);
        }

        if (isset($this->changes['changes']['status'])) {
            $this->changes['changes']['status'][0] = $this->entity->getStatusAsString($this->changes['changes']['status'][0]);
            $this->changes['changes']['status'][1] = $this->entity->getStatusAsString($this->changes['changes']['status'][1]);
        }

        return $this->changes;
    }
}
