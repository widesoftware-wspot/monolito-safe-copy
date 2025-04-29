<?php

namespace Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana;

use Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto\FolderDto;

interface Grafana
{
    public function getFolderByName($folderName);
    public function createFolder(FolderDto $dto);
    public function createDashboard($schema);
    public function removeDashboard($uid);
}
