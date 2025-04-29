<?php
namespace Wideti\DomainBundle\Service\Auth;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Radacct\RadacctServiceAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class AuthService
{
    use MongoAware;
    use EntityManagerAware;
    use RadacctServiceAware;
    use LoggerAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * @param ConfigurationService $configurationService
     * @param Session $session
     * @param CacheServiceImp $cacheService
     * @param GuestDevices $guestDevices
     */
    public function __construct(
        ConfigurationService $configurationService,
        Session $session,
        CacheServiceImp $cacheService,
        GuestDevices $guestDevices
    ) {
        $this->configurationService = $configurationService;
        $this->session              = $session;
        $this->cacheService         = $cacheService;
        $this->guestDevices         = $guestDevices;
    }

    /**
     * @param null $parameters
     * @return array|bool
     */
    public function validateAutoLogin($parameters = null)
    {
        if ($parameters == null || !$parameters['guestMacAddress']) {
            return false;
        }

        try {
            $lastAccessByDevice = $this->guestDevices->getLastAccessWithSpecificDevice(
                $parameters['client'],
                $parameters['guestMacAddress'],
                $parameters['days']
            );

            if ($lastAccessByDevice == null) {
                return false;
            }

            $hasChangePassword = $lastAccessByDevice->getHasChangePassword();
            if ($hasChangePassword){
                echo $hasChangePassword ;
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->addCritical("Fail to find access from client with message: ". $e->getMessage());
            return false;
        }

        $idMysql = $lastAccessByDevice->getGuest()->getId();

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql($idMysql);

        if ($guest === null) {
            return false;
        }


        return [
            'deviceLastAccess'  => $lastAccessByDevice,
            'guest'             => $guest,
            'password'          => $guest->getPassword(),
        ];
    }

    public function isActiveAutoLogin(Nas $nas = null)
    {
        $client = $this->session->get('wspotClient');
        return (bool)$this->configurationService->get($nas, $client, 'auto_login');
    }

    public function formatMacAddress($macAddress)
    {
        $macAddress = preg_replace("/[^A-F0-9]+/i", "", $macAddress);
        $result     = '';

        while (strlen($macAddress) > 0) {
            $sub         = substr($macAddress, 0, 2);
            $result     .= $sub . '-';
            $macAddress  = substr($macAddress, 2, strlen($macAddress));
        }
        return substr($result, 0, strlen($result) - 1);
    }
}
