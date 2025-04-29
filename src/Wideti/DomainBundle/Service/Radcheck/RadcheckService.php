<?php
namespace Wideti\DomainBundle\Service\Radcheck;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\DomainBundle\Entity\Radcheck;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class RadcheckService
{
    use EntityManagerAware;
    use SessionAware;

    public function setExpirationTime($client, Guest $guest, $time, $module)
    {
        $apTimezone = TimezoneService::DEFAULT_TIMEZONE;

        if ($guest->getRegistrationMacAddress()) {
            $ap = $this->em->getRepository('DomainBundle:AccessPoints')->findOneBy([
                'identifier' => $guest->getRegistrationMacAddress()
            ]);

            if ($ap) {
                $apTimezone = $ap->getTimezone() ?: $apTimezone;
            }
        }

        $guest = $this->em
            ->getRepository('DomainBundle:Guests')
            ->find($guest->getMysql())
        ;

        $radcheck = new Radcheck();
        $radcheck->setClient($this->em->getRepository('DomainBundle:Client')->find($client));
        $radcheck->setAttribute('Expiration');
        $radcheck->setOp(':=');
        $radcheck->setValue($this->convertTime($time));
        $radcheck->setGuest($guest);
        $radcheck->setApTimezone($apTimezone);

        $this->em->persist($radcheck);
        $this->em->flush();

	    $this->session->set('timelimitModule', $module);
    }

    public function removeAllExpirationTime($client)
    {
        return $this->em
            ->getRepository('DomainBundle:Radcheck')
            ->deleteExpiration($client);
    }

    public function removeExpirationTimeByGuest($client, Guest $guest)
    {
        $radcheck = $this->em
            ->getRepository('DomainBundle:Radcheck')
            ->findOneBy([
                'client'    => $client,
                'guest'     => $guest->getMysql(),
                'attribute' => 'Expiration'
            ]);

        if ($radcheck) {
            $this->em->remove($radcheck);
            $this->em->flush();
        }
    }

    public function removeAllExpirationTimeByGuest($clientId, $groupId)
    {
        return $this->em->getRepository('DomainBundle:Radcheck')
            ->deleteAllExpirationTimeByGuest($clientId, $groupId);
    }

    public function convertTime($time)
    {
        $value = strtoupper($time);
        $value = str_replace(['D', 'H', 'M', 'S'], [' DAY', ' HOUR', ' MINUTE', ' SECOND'], $value);

        $time = date('F j Y H:i:s', strtotime('+'.$value));

        return $time;
    }

    public function checkIfGuestHasExpiration(Client $client, $guestId = null)
    {
    	if (!$guestId) return false;

	    $radcheckTimeLeft = $this->em
		    ->getRepository('DomainBundle:Radcheck')
		    ->findOneBy(
			    [
				    'client'    => $client,
				    'guest'     => $guestId,
				    'attribute' => 'Expiration'
			    ]
		    )
	    ;
	    if ($radcheckTimeLeft) {
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
		    return ($now->format('Y-m-d H:i:s') < date('Y-m-d H:i:s', strtotime($radcheckTimeLeft->getValue())));
	    }

	    return false;
    }
}
