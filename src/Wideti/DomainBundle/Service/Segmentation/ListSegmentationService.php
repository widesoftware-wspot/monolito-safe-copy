<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Entity\Client;

interface ListSegmentationService
{
    public function listAll(Client $client);
}
