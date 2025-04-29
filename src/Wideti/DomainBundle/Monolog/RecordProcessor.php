<?php
namespace Wideti\DomainBundle\Monolog;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\Users;

class RecordProcessor
{
    public $container;

    private $record;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function processExtraRecord(array $record)
    {
        $this->record = $record;

        if (array_key_exists('HTTP_HOST', $_SERVER)){
            $this->record['level_name'] .= " - {$_SERVER['HTTP_HOST']}";
            $this->record['extra']['SERVER']['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
        }else{
            $this->record['level_name'] .= " - SCRIPT";
            $this->record['extra']['SERVER']['HTTP_HOST'] = 'N/A';
        }

        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $this->record['extra']['SERVER']['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        } else {
            $this->record['extra']['SERVER']['REQUEST_URI'] = 'N/A';
        }

        if ($_FILES) {
            $this->record['extra']['FILES']    = $_FILES;
        }

        $this->record['extra']['Facebook_login']['session_FBRLH_state'] =
            isset($_SESSION['FBRLH_state']) ? $_SESSION['FBRLH_state'] : " ";
        $this->record['extra']['Facebook_login']['get_state'] = isset($_GET['state']) ? $_GET['state'] : " ";

        $this->getUserInfo();

        return $this->record;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function processLogRecord(array $record)
    {
        $this->record = $record;

        $this->getUserInfo();

        return $this->record;
    }

    /**
     * @return $this
     */
    private function getUserInfo()
    {
        $securityContext = $this->container->get('security.token_storage');

        if ($securityContext->getToken() == null) {
            return true;
        }

        $authorization = $this->container->get('security.authorization_checker');

        if (!$authorization->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->record['extra']['User'] = 'User was not authenticated';

            return true;
        }

        $user = $securityContext->getToken()->getUser();

        if ($user instanceof Users) {
            $this->record['extra']['Users']['name']     = $user->getNome();
            $this->record['extra']['Users']['username'] = $user->getUsername();
        } elseif ($user instanceof Guests) {
            $this->record['extra']['Guests']['email']  = $user->getEmail();
        } else {
            $this->record['extra']['user']['user'] = "User is not identified, CHECK THIS OUT!.";
        }

        return true;
    }
}
