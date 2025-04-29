<?php

namespace Wideti\DomainBundle\Service\Radacct;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface GetAccountingData
 * @package Wideti\DomainBundle\Service\Radacct
 */
interface GetAccountingData
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function get(Request $request);
}