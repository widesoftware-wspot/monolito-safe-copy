<?php

namespace Wideti\DomainBundle\Service\AuthorizationCode;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\GuestAuthCode;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AuthorizationCodeService
{
    use EntityManagerAware;
    use SessionAware;

    protected $digits   = 4  ;
    protected $hashSize = 16 ;

    /**
     * @var Guest
     */
    protected $guest;

    public function get(Guest $guest)
    {
        return $this->em
        ->getRepository('DomainBundle:GuestAuthCode')
        ->findOneByGuest($guest->getMysql());
    }

    public function create($type, Guest $guest)
    {
        $existCode = $this->em
            ->getRepository('DomainBundle:GuestAuthCode')
            ->findOneByGuest($guest->getMysql());

        if ($existCode !== null) {
            return $existCode->getCode();
        }

        $this->guest = $this->em
            ->getRepository('DomainBundle:Guests')
            ->findOneById($guest->getMysql());

        $method      = "generate" . ucwords($type);
        $code        = $this->$method();

        $this->saveAuthCode($code);

        return $code;
    }

    public function saveAuthCode($code)
    {
        $authCode = new GuestAuthCode();
        $authCode->setCode($code);
        $authCode->setGuest($this->guest);

        $this->em->persist($authCode);
        $this->em->flush();
    }

    public function generateCode()
    {
        return rand(pow(10, $this->digits - 1), pow(10, $this->digits) - 1);
    }

    public function generateHash()
    {
        return substr(sha1(uniqid()), 0, $this->hashSize);
    }
}
