<?php

namespace Wideti\DomainBundle\Service\AccessPointDictionary;

interface AccessPointDictionaryService
{
    /**
     * @param $reference
     * @return null|string
     * @internal param $accessPointMac
     */
    public function getApMacAddressFromDictionary($reference);
}