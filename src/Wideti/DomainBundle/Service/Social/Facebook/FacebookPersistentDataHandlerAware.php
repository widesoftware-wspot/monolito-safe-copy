<?php


namespace Wideti\DomainBundle\Service\Social\Facebook;

trait FacebookPersistentDataHandlerAware
{
    /**
     * @var FacebookPersistentDataHandler
     */
    protected $facebookPersistentDataHandler;

    public function setFacebookPersistentDataHandler(FacebookPersistentDataHandler $dataHandler)
    {
        $this->facebookPersistentDataHandler = $dataHandler;
    }
}