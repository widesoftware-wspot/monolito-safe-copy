<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;

interface BuildAccessPointFromApiCreateDtoService
{
    /**
     * @param CreateAccessPointDto $createAccessPointDto
     * @return AccessPoints
     */
    public function getEntity(CreateAccessPointDto $createAccessPointDto);
}
