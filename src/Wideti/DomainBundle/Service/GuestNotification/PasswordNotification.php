<?php

namespace Wideti\DomainBundle\Service\GuestNotification;

use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Base\EmailNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Senders\EmailServiceAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class PasswordNotification implements EmailNotificationInterface
{
    use MongoAware;
    use EmailServiceAware;
    use GuestServiceAware;

    public function send(Nas $nas = null, $params = [])
    {
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ])
        ;

        if ($this->guestService->hasEmailFieldInProperties($guest)) {
            $this->emailService->password($guest, $nas, $params);
        }
    }
}
