<?php
namespace Wideti\DomainBundle\Service\Watchdog;

use Doctrine\ODM\MongoDB\DocumentManager;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MailerAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ClientsAreNotUsingReport implements WatchdogServiceInterface
{
    use EntityManagerAware;
    use TwigAware;
    use MailHeaderServiceAware;
    use MailerServiceAware;
    use MailerAware;

    /**
     * @var DocumentManager
     */
    private $documentManager;
    /**
     * @var \MongoClient
     */
    private $mongoClient;

    /**
     * BandwidthPolicyWriter constructor.
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->mongoClient = $this->documentManager->getConnection()->getMongoClient();
    }

    public function execute($forHTMLReport = null, $status = null)
    {
        if (is_null($status)) {
            $status = 1;
        }

        /**
         * @var Client[] $clients
         * */
        $clients = $this->em
            ->getRepository('DomainBundle:Client')
            ->findBy([
                'status' => $status
            ]);

        $results     = [];
        $periodStart = 6;

        foreach ($clients as $client) {
            $guest = $this->getLastAccessByClient($client);

            $users = $client->getUsers();
            $userEmail = [];

            foreach ($users as $user) {
                if ($user->getStatus() == Users::ACTIVE) {
                    array_push($userEmail, $user->getUsername());
                }
            }

            if (!$guest) {
                array_push($results, [
                    'client'                => $client,
                    'days_without_access'   => $client->getCreated()->diff(new \DateTime())->days,
                    'last_access'           => 'sem acesso',
                    'user_email'            => implode(", ", $userEmail)
                ]);
            } else {
                $lastAccess = isset($guest["lastAccess"]) ? $guest["lastAccess"] : $guest["created"];
                $lastAccess = $lastAccess
                    ? date("Y-m-d H:i:s", $lastAccess->sec)
                    : $client->getCreated()->format("Y-m-d H:i:s")
                ;

                $date1 = new \DateTime($lastAccess);
                $date2 = new \DateTime('now');
                $diff = date_diff($date1, $date2);

                if ($diff->days >= $periodStart) {
                    array_push($results, [
                        'client'                => $client,
                        'days_without_access'   => $diff->days,
                        'last_access'           => date_format($date1, 'd/m/Y'),
                        'user_email'             => implode(", ", $userEmail)
                    ]);
                }
            }
        }

        $this->arraySortByColumn($results, 'days_without_access');

        if (!is_null($forHTMLReport) && $forHTMLReport == true) {
            return $results;
        }

        $this->send([
            'deliveryTo' => $this->emailHeader->getCommercialRecipient(),
            'results'    => $results
        ]);
    }

    private function arraySortByColumn(&$arr, $col, $dir = SORT_DESC)
    {
        $sort_col = [];
        foreach ($arr as $key => $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }

    public function send($params = [])
    {
        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('[Mambo WiFi] Clientes que não estão utilizando o Mambo WiFi')
            ->from(['WSpot' => $this->emailHeader->getSender()])
            ->to($params['deliveryTo'])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Admin:clientsAreNotUsing.html.twig',
                    [
                        'results' => $params['results']
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    private function getLastAccessByClient(Client $client)
    {
        $lastGuestOnMySQL = $this->em
            ->getRepository("DomainBundle:Guests")
            ->getLastGuestIdByClient($client);

        $clientDatabase = StringHelper::slugDomain($client->getDomain());
        $database       = $this->mongoClient->$clientDatabase;
        $collection     = $database->guests;

        return $collection
            ->findOne([
                'mysql' => $lastGuestOnMySQL
            ]);
    }
}
