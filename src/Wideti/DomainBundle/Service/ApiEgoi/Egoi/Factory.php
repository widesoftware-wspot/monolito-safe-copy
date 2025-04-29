<?php

namespace Wideti\DomainBundle\Service\ApiEgoi\Egoi;

use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Api\EgoiApiRestImpl;
use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Api\EgoiApiSoapImpl;
use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Api\EgoiApiXmlRpcImpl;
use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Protocol;
use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Api;

abstract class Factory {
    static function getApi($protocol) {
        switch($protocol) {
            case Protocol::Rest:
                return new EgoiApiRestImpl();
            case Protocol::Soap;
                return new EgoiApiSoapImpl();
            case Protocol::XmlRpc:
                return new EgoiApiXmlRpcImpl();
        }
    }
}
