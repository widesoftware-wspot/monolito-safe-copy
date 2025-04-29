<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

class Guests implements ComputerInterface
{
    use ComputerAware;

    public function __construct(\Wideti\DomainBundle\Entity\Guests $entity, array $changes)
    {
        $this->entity  = $entity;
        $this->changes = $changes;
    }

    public function compute()
    {
        if (isset($this->changes['id'])) {
            unset($this->changes['id']);
        }

        return $this->changes;
    }
}
