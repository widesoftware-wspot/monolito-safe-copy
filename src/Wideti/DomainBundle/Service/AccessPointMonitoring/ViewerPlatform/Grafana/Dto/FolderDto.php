<?php

namespace Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto;

class FolderDto
{
    public $uid;
    public $title;

    /**
     * FolderDto constructor.
     * @param $uid
     * @param $title
     */
    public function __construct($uid, $title)
    {
        $this->uid = $uid;
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
}
