<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class GuestRegisterMode extends \Twig_Extension
{
    use MongoAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('guest_register_mode', array($this, 'getGuestRegisterMode')),
        );
    }

    public function getGuestRegisterMode($guest)
    {
        if ($guest->getRegisterMode()) {
            if ($guest->getRegisterMode() == Social::OAUTH) return "Integração";
            return $guest->getRegisterMode();
        }

        $registerMode = $guest->getSocial()->first();

        if ($registerMode) {
            switch ($registerMode->getType()) {
                case Social::FACEBOOK:
                    $registerMode = 'Facebook';
                    break;
                case Social::TWITTER:
                    $registerMode = 'Twitter';
                    break;
                case Social::GOOGLE:
                    $registerMode = 'Google';
                    break;
                case Social::INSTAGRAM:
                    $registerMode = 'Instagram';
                    break;
                case Social::LINKEDIN:
                    $registerMode = 'LinkedIn';
                    break;
            }

            return $registerMode;
        }

        return 'Formulário';
    }

    public function getName()
    {
        return 'guest_register_mode';
    }
}
