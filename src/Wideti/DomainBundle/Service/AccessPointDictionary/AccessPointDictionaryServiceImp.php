<?php

namespace Wideti\DomainBundle\Service\AccessPointDictionary;

use Doctrine\ODM\MongoDB\DocumentManager;

class AccessPointDictionaryServiceImp implements AccessPointDictionaryService
{
    /** @var DocumentManager */
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @param $reference
     * @return null|string
     * @internal param $accessPointMac
     */
    public function getApMacAddressFromDictionary($reference)
    {
        $dictionary = $this->documentManager
            ->getRepository('DomainBundle:AccessPointDictionary\AccessPointDictionary')
            ->findOneBy([
                'references' => [
                    '$in' => [$reference]
                ]
            ]);

        if (!$dictionary) {
            return null;
        }
        $values = $dictionary->getValues();
        return $values[array_rand($values)];
    }
}