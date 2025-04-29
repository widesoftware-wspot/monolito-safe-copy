<?php

namespace Wideti\DomainBundle\Service\RegisterAccessPoints;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\WhiteLabel\WhiteLabelService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class RegisterAccessPointsService
{
    use EntityManagerAware;
    use ElasticSearchAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;
    use LoggerAware;
	/**
	 * @var WhiteLabelService
	 */
	private $whiteLabelService;

	/**
	 * RegisterAccessPointsService constructor.
	 * @param WhiteLabelService $whiteLabelService
	 */
	public function __construct(WhiteLabelService $whiteLabelService)
	{
		$this->whiteLabelService = $whiteLabelService;
	}

	public function init()
    {
        $clients = $this->em
            ->getRepository('DomainBundle:Client')
            ->findAll();

        foreach ($clients as $client) {
            $this->registerAccessPoints($client);
        }
    }

    public function registerAccessPoints(Client $client)
    {
        $countContractedAps = $client->getContractedAccessPoints();

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $output->writeln("<info>Client: ".$client->getDomain()." - SELECIONADO..........</info>");

        $activatedAps = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::ACTIVE
            ]);

        $registeredAps = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getRegisteredAps($client);

        $registeredMacAddresses = [];

        foreach ($registeredAps as $ap) {
            $ap = str_replace(':', '-', $ap['friendlyName']);
            if ($ap != null && $ap != "") {
                array_push($registeredMacAddresses, $ap);
            }
        }

        $search = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "query" => [
                        "match" => [
                            "client_id" => $client->getId()
                        ]
                    ],
                    "filter" => [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "calledstation_name" => $registeredMacAddresses
                                ]
                            ],
                            "filter" => [
                                "range" => [
                                    "acctstarttime" => [
                                        "gte" => "now-3d",
                                        "lte" => "now"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "group_by_ap" => [
                    "terms" => [
                        "field" => "calledstation_name",
                        "order" => ["_term" => "asc"]
                    ]
                ]
            ]
        ];

        $calledStations = $this->elasticSearchService->search('radacct', $search, ElasticSearch::CURRENT);
        $calledStations = $calledStations['aggregations']['group_by_ap']['buckets'];

        $countNotRegisteredAps = count($calledStations);

        if (empty($calledStations)) {
            $output->writeln("<info>Client: ".$client->getCompany()." - There is no Station to be registered</info>");
        }

        $calledStationsMacAddress = [];

        foreach ($calledStations as $mac) {
            if (!$mac['key'] == '') {
                array_push(
                    $calledStationsMacAddress,
                    $mac['key']
                );
            }
        }

        $total = count($calledStationsMacAddress);

        for ($current = 0; $current < $total; $current++) {
            $checkExists = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointByIdentifier($calledStationsMacAddress[$current], $client);

            if (empty($calledStationsMacAddress[$current]) || count($checkExists) > 0) {
                continue;
            }

            $accessPoint = new AccessPoints();
            $next        = $current + 1;

            $accessPoint->setClient($client);
            $accessPoint->setFriendlyName($calledStationsMacAddress[$current]);
            $accessPoint->setIdentifier($calledStationsMacAddress[$current]);

            // Checking last record.
            if (($total - 1) == $current) {
                $accessPoint->setType('singleRadio');
                $this->saveAccessPoint($accessPoint);
                continue;
            }

            $currentMac =  substr($calledStationsMacAddress[$current], 0, -2);
            $nextMac    =  substr($calledStationsMacAddress[$next], 0, -2);

            // Chekcing if the mac address has a 5.4ghz partner
            if ($currentMac === $nextMac) {
                $accessPoint->setMac5ghz($calledStationsMacAddress[$next]);
                $accessPoint->setType('dualRadio');

                $current++;
            } else {
                $accessPoint->setType('singleRadio');
            }

            $this->saveAccessPoint($accessPoint);
        }

        $this->checkLimitHasBeenReached(
            $client,
            $calledStations,
            $countContractedAps,
            $countNotRegisteredAps,
            $activatedAps
        );
    }

    public function checkLimitHasBeenReached(
        $client,
        $calledStations,
        $countContractedAps,
        $countNotRegisteredAps,
        $countActivatedAps
    ) {
        if ($calledStations && $countContractedAps < ($countNotRegisteredAps + $countActivatedAps)) {
            $accessPoints = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointsList($client->getId());

            $this->sendMailLimitAps($client, $accessPoints);
        }
    }

    public function saveAccessPoint(AccessPoints $ap)
    {
        try {
            $this->em->persist($ap);
            $this->em->flush();
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $output = new \Symfony\Component\Console\Output\ConsoleOutput();

            if (strpos($message, 'Duplicate entry') !== false) {
                $message = "Access Point: " . $ap->getIdentifier() . " - Already registered";
            }

            $output->writeln("<info>".$message."</info>");

            $this->logger->addWarning($message);
        }
    }

    public function sendMailLimitAps($client, $arrayAps)
    {
        $countActiveAps  = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::ACTIVE
            ]);

        $countInactiveAps  = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::INACTIVE
            ]);

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Cadastro de APs - Limite de quantidade atingido')
            ->from(['Mambo Wifi' => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getAdminRecipient())
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:AccessPoints:mail.html.twig',
                    [
                        'client'           => $client,
                        'countAps'         => $countActiveAps + $countInactiveAps,
                        'countActiveAps'   => $countActiveAps,
                        'countInactiveAps' => $countInactiveAps,
                        'arrayAps'         => $arrayAps,
                        'whiteLabel'       => $this->whiteLabelService->getDefaultWhiteLabel()
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }
}
