<?php

namespace Wideti\DomainBundle\Service\Segmentation\Equality;

interface EqualityFactory
{
    public function get($identifier, $equality);
}
