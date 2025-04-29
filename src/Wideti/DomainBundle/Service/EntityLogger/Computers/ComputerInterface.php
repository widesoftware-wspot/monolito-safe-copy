<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Computers;

interface ComputerInterface
{
    public function compute();
    public function getChanges();
    public function getEntity();
}
