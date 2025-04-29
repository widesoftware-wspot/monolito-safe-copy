<?php
namespace Wideti\WebFrameworkBundle\Aware;

use Knp\Component\Pager\Paginator;

/**
 * Class PaginatorAware
 * @package Wideti\WebFrameworkBundle\Aware
 * - [ setPaginator, ["@knp_paginator"] ]
 */
trait PaginatorAware
{
    /**
     * @var Paginator
     */
    protected $paginator;

    public function setPaginator(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }
}
