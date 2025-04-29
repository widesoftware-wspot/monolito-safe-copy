<?php

namespace Wideti\DomainBundle\Service\ApiRDStation;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\DBAL\DBALException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\ApiRDStation;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Queue\Message;
use Wideti\DomainBundle\Service\Queue\QueueService;
use Wideti\DomainBundle\Service\Sns\SnsService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

/**
 * Class ApiRDStationService
 * @package Wideti\DomainBundle\Service\ApiRDStation
 * ### DOCUMENTATION ###
 * url: https://wideti.atlassian.net/wiki/spaces/DES/pages/471203845/Integra+o+com+RD+Station+-+Fluxo+da+integra+o
 */
class ApiRDStationService
{
    use MongoAware;
    use EntityManagerAware;
    use LoggerAware;
    use PaginatorAware;
    use SecurityAware;
    use ModuleAware;
    use SessionAware;

    const ID_ON_CREATE          = 'cadastro_mambo_wifi';
    const ID_ON_CREATE_WL       = 'cadastro_rede_wifi';
    const ID_FIRST_ACCESS       = 'primeiro_login_diario_mambo_wifi';
    const ID_FIRST_ACCESS_WL    = 'primeiro_login_diario_rede_wifi';
    const ID_BATCH_CONVERSIONS  = 'conversao_total_em_lote';

    private $awsKey;
    private $awsSecret;
    private $awsSnsRegion;
    private $awsSnsTopic;
    private $awsSqsRegion;
    private $awsSqsName;

    /**
     * @var SnsService
     */
    private $sns;
    /**
     * @var QueueService
     */
    private $sqs;

    /**
     * ApiRDStationService constructor.
     * @param $key
     * @param $secret
     * @param $awsSnsRegion
     * @param $awsSnsTopic
     * @param $awsSqsRegion
     * @param $awsSqsName
     */
    public function __construct($key, $secret, $awsSnsRegion, $awsSnsTopic, $awsSqsRegion, $awsSqsName)
    {
        $this->awsKey       = $key;
        $this->awsSecret    = $secret;

        $this->awsSnsRegion = $awsSnsRegion;
        $this->awsSnsTopic  = $awsSnsTopic;
        $this->sns          = new SnsService($this->awsKey, $this->awsSecret, $this->awsSnsTopic, $this->awsSnsRegion);

        $this->awsSqsRegion = $awsSqsRegion;
        $this->awsSqsName   = $awsSqsName;
        $this->sqs          = new QueueService($this->awsKey, $this->awsSecret, $this->awsSqsRegion, $this->awsSqsName);
    }

	/**
	 * @param ApiRDStation $apiRDStation
	 * @return ApiRDStation
	 * @throws DBALException
	 */
    public function create(ApiRDStation $apiRDStation)
    {
	    if (!$apiRDStation->getClient()) {
		    $client = $this->em
			    ->getRepository("DomainBundle:Client")
			    ->find($this->getLoggedClient());

		    if ($client == null) {
			    throw new NotFoundException('Client not found');
		    }

		    $apiRDStation->setClient($client);
	    }

	    try {
		    $this->em->persist($apiRDStation);
		    $this->em->flush();
	    } catch (\Exception $e) {
		    throw new DBALException($e->getMessage());
	    }

	    return $apiRDStation;
    }

	/**
	 * @param ApiRDStation $apiRDStation
	 * @return ApiRDStation
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function update(ApiRDStation $apiRDStation)
    {
	    $this->em->persist($apiRDStation);
	    $this->em->flush();

	    return $apiRDStation;
    }

	/**
	 * @param ApiRDStation $apiRDStation
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function delete(ApiRDStation $apiRDStation)
    {
        $this->em->remove($apiRDStation);
        $this->em->flush();
    }

    public function checkAccessPointAlreadyExists(Client $client, $id, $ap = null)
    {
        if ($ap) {
            if ($id) {
                return boolval($this->em
                    ->getRepository('DomainBundle:ApiRDStation')
                    ->getByAccessPointAndIntegrationId($client, $ap->getId(), $id));
            }
            return boolval($this->em
	            ->getRepository('DomainBundle:ApiRDStation')
	            ->getByAccessPoint($client, $ap));
        }

        if ($id) {
            return boolval($this->em
	            ->getRepository('DomainBundle:ApiRDStation')
	            ->getByAccessPointAndIntegrationId($client, false, $id));
        }

	    return boolval(
		    $this->em
			    ->getRepository('DomainBundle:ApiRDStation')
			    ->findOneBy([
				    'client'         => $client,
				    'inAccessPoints' => 0
			    ])
	    );
    }

    public function conversion($token, Nas $nas = null, Guest $guest, $leadIdentifier)
    {
        $message = new Message();

        $accessPoint = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'identifier' => $nas->getAccessPointMacAddress()
            ]);

        $authorizeEmail = $guest->getAuthorizeEmail() == "1" ? 'SIM' : 'NÃO';

        $trafficSource = 'Wi-Fi';
        if (isset($_COOKIE["__trf_src"])) {
            $trafficSourceEncode = str_replace('encoded_', "",
                trim($_COOKIE["__trf_src"]));
            $trafficSourceDecode
                = json_decode(base64_decode($trafficSourceEncode), true);
            $trafficSource = $trafficSourceDecode["first_session"]["value"];
        }

        $domain = $this->getLoggedClient()->getDomain();

        if (!$this->getLoggedClient()->isWhiteLabel()) {
            $domain .= '.mambowifi.com';
        }

        $object = [
            "token_rdstation"           => $token,
            "identificador"             => $leadIdentifier,
            "autoriza_receber_email"    => $authorizeEmail,
            "email"                     => $guest->getProperties()['email'],
            "horario"                   => date('d/m/Y H:i:s'),
            "mac_ponto_de_acesso"       => $accessPoint ? $accessPoint->getIdentifier() : $guest->getRegistrationMacAddress(),
            "nome_ponto_de_acesso"      => $accessPoint ? $accessPoint->getFriendlyName() : $guest->getRegistrationMacAddress(),
            "dominio"                   => $domain,
            "traffic_source"            => $trafficSource,
            "traffic_medium"            => "social",
        ];

        foreach ($guest->getProperties() as $key=>$value) {
            if ($key == 'data_nascimento') {
                $birthday = new \DateTime(date('Y-m-d H:i:s', $value->sec));
                $value = $birthday->format('d/m/Y');
            } elseif ($key == 'zip_code') {
                $object['cep'] = $value;
            }
            $object[$key] = $value;
        }

        $message->setContent(json_encode($object));

        $this->trySendingSQSMessage($message);
    }

    public function sendToSns($rdStationToken)
    {
        $message = $this->getLoggedClient()->getDomain() . "|" . $rdStationToken;

        try {
            $this->sns->getClient()->publish([
                "TopicArn" => $this->sns->getArn(),
                "Message"  => $message
            ]);
        } catch (\Exception $e) {
            $this->logger->addCritical('Fail to send RD message to SNS. Message: '. $e->getMessage());
        }
    }

    public function batchAllGuestsConversions($clientDomain, $rdStationToken)
    {
        $this->setDefaultDatabaseOnMongo($clientDomain);

        /**
         * @var $guest Guest
         * @var $accessPoint AccessPoints
         */
        $guests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findAll();

        $message = new Message();
        $lastAp  = '';

        foreach ($guests as $guest) {
            if ($lastAp != $guest->getRegistrationMacAddress()) {
                $accessPoint = $this->em
                    ->getRepository('DomainBundle:AccessPoints')
                    ->findOneBy([
                        'identifier' => $guest->getRegistrationMacAddress()
                    ]);
            }

            $lastAp = $guest->getRegistrationMacAddress();

            $domain = $this->getLoggedClient()->getDomain();

            if (!$this->getLoggedClient()->isWhiteLabel()) {
                $domain .= '.mambowifi.com';
            }

            $object = [
                "token_rdstation"       => $rdStationToken,
                "identificador"         => self::ID_BATCH_CONVERSIONS,
                "email"                 => $guest->getProperties()['email'],
                "horario"               => $guest->getCreated()->format('d/m/Y H:i:s'),
                "mac_ponto_de_acesso"   => $accessPoint ? $accessPoint->getIdentifier() : $guest->getRegistrationMacAddress(),
                "nome_ponto_de_acesso"  => $accessPoint ? $accessPoint->getFriendlyName() : $guest->getRegistrationMacAddress(),
                "dominio"               => $domain,
            ];

            foreach ($guest->getProperties() as $key=>$value) {
                if ($key == 'data_nascimento') {
                    $birthday = new \DateTime(date('Y-m-d H:i:s', $value->sec));
                    $value = $birthday->format('d/m/Y');
                } elseif ($key == 'zip_code') {
                    $object['cep'] = $value;
                }
                $object[$key] = $value;
            }

            $message->setContent(json_encode($object));
            $this->trySendingSQSMessage($message);
        }
    }

    private function setDefaultDatabaseOnMongo($domain)
    {
        $manager = $this->mongo;

        $manager
            ->getConfiguration()
            ->setDefaultDB($domain)
        ;

        $this->mongo->create(
            $manager->getConnection(),
            $manager->getConfiguration(),
            $manager->getEventManager()
        );
    }

    private function trySendingSQSMessage($message)
    {
        try {
            $this->sqs->sendMessage($message);
        } catch (\Exception $exception) {
            $this->logger->addCritical("SQS Message error: {$exception->getMessage()}");
        }
    }
}
