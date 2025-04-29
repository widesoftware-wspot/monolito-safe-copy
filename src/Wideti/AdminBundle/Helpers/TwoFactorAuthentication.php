<?php

namespace Wideti\AdminBundle\Helpers;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wideti\DomainBundle\Entity\Users;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Google\Authenticator\GoogleAuthenticator;


/**
 * Generate the authentication code and take care of the flag in the session.
 */
class TwoFactorAuthentication
{
    /**
     * @var EntityManager $em
     */
    private $em;


    /**
     * Construct the helper service for Two Factor authenticator
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Validates the code, which was entered by the user
     * @param Users $user
     * @param $code
     * @return bool
     */
    public function checkCode(Users $user, $code)
    {
        $g     = new GoogleAuthenticator();

        return $g->checkCode($user->getTwoFactorAuthenticationSecret(), $code);


    }

    /**
     * Generates the attribute key for the session
     * @param SessionInterface $session
     * @return string
     */
    public function getSessionKey(SessionInterface $session)
    {
        return sprintf('two_factor_%s', $session->getId());
    }

    /**
     * Generates the QRCode to Google Two Factor Authentication to specified user
     * @param Users $user
     * @return string
     */
    public function generateTwoFactorAuthenticationQRCodeURL(Users $user){

        $g          = new GoogleAuthenticator();
        $username   = $user->getUsername();
        $secret     = $user->getTwoFactorAuthenticationSecret();

        $url = $g->getURL($username, "WSpot Wifi", $secret);

        return $this->replaceUrlToUseGoQrApi($url);

    }

    /**
     * Generates a new secret to Google Two Factor Authentication
     * @return string
     */
    public function generateNewTwoFactorAuthenticationSecret(){

        $g          = new GoogleAuthenticator();
        return $g->generateSecret();

    }

    /**
     * Replaces the URL to use the goQR API instead of Google Charts Infographics(Discontinued)
     * @return string
     */
    private function replaceUrlToUseGoQrApi($url){

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['query'])) {
        
            $queryParams = $parsedUrl['query'];
            parse_str($queryParams, $queryArray);
            if (isset($queryArray['chl']) && isset($queryArray['chs'])) {
                $chlValue = $queryArray['chl'];
                $chsValue = $queryArray['chs'];

                return sprintf(
                    'https://api.qrserver.com/v1/create-qr-code/?size=%1$dx%1$d&data=%2$s&ecc=M',
                    $chsValue,
                    $chlValue
                );
            }
        }
        return $url;
    }
}
