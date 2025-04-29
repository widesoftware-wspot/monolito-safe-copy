<?php

namespace Wideti\DomainBundle\Service\GuestSocial;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Guest\Social;

class GuestSocialService
{
    use MongoAware;
    use SessionAware;
    use CustomFieldsAware;

    public function getLoginField()
    {
        $loginField = $this->customFieldsService->getLoginField()[0];
        return $loginField->getIdentifier();
    }

    public function verifyCredentials(Guest $guest, array $data)
    {
        $socialGuest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'properties.' . $this->getLoginField() => $guest->getProperties()[$this->getLoginField()],
                'social' => [
                    'id'   => (string)$data['social']['id'],
                    'type' => (string)$data['social']['type']
                ]
            ]);

        if ($socialGuest) {
            return $socialGuest;
        }
        return false;
    }

    public function create(Guest $guest, array $data)
    {
        if (!isset($data['social']['id'])) {
            return;
        }

        $object = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'properties.' . $this->getLoginField() => $guest->getProperties()[$this->getLoginField()],
                'social' => [
                    'id'   => (string)$data['social']['id'],
                    'type' => (string)$data['social']['type']
                ]
            ]);

        if (!$object) {
            $guestSocial = new Social();
            $guestSocial->setId($data['social']['id']);
            $guestSocial->setType($data['social']['type']);

            $guest->addSocial($guestSocial);

            $this->mongo->persist($guestSocial);
            $this->mongo->flush();
        }
    }

    public function facebookFields(Guest $guest, $fields)
    {
        if ($fields) {
            $object = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'properties.' . $this->getLoginField() => $guest->getProperties()[$this->getLoginField()]
                ]);

            $object->setFacebookFields($fields);
            $this->mongo->persist($object);
            $this->mongo->flush();
        }
    }
}
