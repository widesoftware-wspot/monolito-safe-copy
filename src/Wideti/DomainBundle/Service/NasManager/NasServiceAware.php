<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 29/09/16
 * Time: 10:33
 */

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Service\NasManager\NasService;

/**
 *
 * Usage: - [ setNasService, ["@core.service.nas"] ]
 */
trait NasServiceAware
{
    /**
     * @var NasService
     */
    protected $nasService;

    public function setNasService(NasService $service)
    {
        $this->nasService = $service;
    }
}
