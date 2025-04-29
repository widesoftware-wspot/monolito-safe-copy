<?php

namespace Wideti\DomainBundle\Cli;

/**
 * Interface Script
 * @package Wideti\DomainBundle\Cli
 */
interface Script
{
    /**
     * @return mixed
     */
    public function run();
}