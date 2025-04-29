<?php

namespace Wideti\ApiBundle\Helpers;

use Wideti\DomainBundle\Entity\Client;

class ConverterHelper
{
    /**
     * Convert a Json Object from RD STATION into Client Entity Object
     * @param array $rdJsonObject
     * @return Client
     */
    public static function convertJsonToClient(array $rdJsonObject)
    {
        $entity = new Client();
        $data   = $rdJsonObject;

        $document = isset($data['document']) ? $data['document'] : null;
        $address  = isset($data['address']) ? $data['address'] : null;
        $district = isset($data['district']) ? $data['district'] : null;
        $city     = isset($data['city']) ? $data['city'] : null;
        $state    = isset($data['state']) ? $data['state'] : null;
        $zipCode  = isset($data['zipCode']) ? $data['zipCode'] : null;

        $entity->setCompany($data['company']);
        $entity->setDocument($document);
        $entity->setStatus(Client::STATUS_INACTIVE);
        $entity->setDomain($data['domain']);
        $entity->setAddress($address);
        $entity->setDistrict($district);
        $entity->setCity($city);
        $entity->setState($state);
        $entity->setZipCode($zipCode);

        $entity->setSmsCost('0,13');
        $entity->setContractedAccessPoints('1');
        $entity->setClosingDate('10');

        return $entity ;
    }
}
