<?php

namespace Wideti\DomainBundle\Service\ApiEgoi;

use Aws\Sns\Exception\NotFoundException;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\ApiEgoi;
use Wideti\DomainBundle\Exception\GuestNotFoundException;
use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Factory;
use Wideti\DomainBundle\Service\ApiEgoi\Egoi\Protocol;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Queue\Message;
use Wideti\DomainBundle\Service\Queue\QueueService;
use Wideti\DomainBundle\Service\Sns\SnsService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ApiEgoiService
{
    use EntityManagerAware;
    use LoggerAware;
    use PaginatorAware;
    use SecurityAware;
    use ModuleAware;
    use SessionAware;
    use MongoAware;

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

    private $api;

    /**
     * ApiEgoiService constructor.
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
        $this->api          = Factory::getApi(Protocol::Soap);
    }

	/**
	 * @param ApiEgoi $apiEgoi
	 * @return ApiEgoi
	 * @throws DBALException
	 */
    public function create(ApiEgoi $apiEgoi)
    {
	    if (!$apiEgoi->getClient()) {
		    $client = $this->em
			    ->getRepository("DomainBundle:Client")
			    ->find($this->getLoggedClient());

		    if ($client == null) {
			    throw new NotFoundException('Client not found');
		    }

		    $apiEgoi->setClient($client);
	    }

        try {
            $this->em->persist($apiEgoi);
            $this->em->flush();
        } catch (\Exception $e) {
	        throw new DBALException($e->getMessage());
        }

        return $apiEgoi;
    }

	/**
	 * @param ApiEgoi $apiEgoi
	 * @return ApiEgoi
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function update(ApiEgoi $apiEgoi)
    {
        $this->em->persist($apiEgoi);
        $this->em->flush();

        return $apiEgoi;
    }

	/**
	 * @param ApiEgoi $apiEgoi
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function delete(ApiEgoi $apiEgoi)
    {
        $this->em->remove($apiEgoi);
        $this->em->flush();
    }

	/**
	 * @param $client
	 * @param $id
	 * @param null|AccessPoints $ap
	 * @return bool
	 */
    public function checkAccessPointAlreadyExists($client, $id, $ap = null)
    {
	    if ($ap) {
            if ($id) {
                return boolval($this->em
                    ->getRepository('DomainBundle:ApiEgoi')
                    ->getByAccessPointAndIntegrationId($client, $ap->getId(), $id));
            }
            return boolval($this->em
                ->getRepository('DomainBundle:ApiEgoi')
                ->getByAccessPoint($client, $ap));
        }

        if ($id) {
            return boolval($this->em
                ->getRepository('DomainBundle:ApiEgoi')
                ->getByAccessPointAndIntegrationId($client, false, $id));
        }

        return boolval(
            $this->em
            ->getRepository('DomainBundle:ApiEgoi')
            ->findOneBy([
            	'client'         => $client,
                'inAccessPoints' => 0
            ])
        );
    }

    public function getLists($token)
    {
        $result = $this->api->getLists([
            'apikey' => $token
        ]);

        if (!$result || ($result && array_key_exists('ERROR', $result))) {
            return [];
        }

        return $result;
    }

    public function subscribe(ApiEgoi $egoi, Guest $guest)
    {
        $message = new Message();

        $properties = $guest->getProperties();

        $object = [
            'apikey' => $egoi->getToken(),
            'listID' => $egoi->getList(),
            'email'  => array_key_exists('email', $properties) ? $properties['email'] : 'email_nao_informado'
        ];

        if (array_key_exists('name', $properties)) {
            $explodeName = explode(' ', $properties['name']);
            $object['first_name'] = $explodeName[0];
            $object['last_name']  = end($explodeName);
        }

        if (array_key_exists('data_nascimento', $properties)) {
            $birthday = new \DateTime(date('Y-m-d H:i:s', $properties['data_nascimento']->sec));
            $object['birth_date'] = $birthday->format('Y-m-d');
        }

        if (array_key_exists('phone', $properties)) {
            $dialCode = array_key_exists('dialCodePhone', $properties)
                ? $properties['dialCodePhone']
                : '55';

            $object['cellphone'] = "{$dialCode}-{$properties['phone']}";
        }

        $message->setContent(json_encode($object));
        $this->trySendingSQSMessage($message);
    }

    public function sendToSns($apiToken)
    {
        $message = $this->getLoggedClient()->getDomain() . "|" . $apiToken;

        try {
            $this->sns->getClient()->publish([
                "TopicArn" => $this->sns->getArn(),
                "Message"  => $message
            ]);
        } catch (\Exception $e) {
            $this->logger->addCritical('Fail to send E-goi message to SNS. Message: '. $e->getMessage());
        }
    }

	/**
	 * @param $clientDomain
	 * @param $listToken
	 * @throws GuestNotFoundException
	 * @throws \Doctrine\ODM\MongoDB\MongoDBException
	 */
    public function batchAllGuestsSubscribe($clientDomain, $listToken)
    {
        $this->setDefaultDatabaseOnMongo($clientDomain);

        $guests = $this->mongo
            ->createQueryBuilder('Wideti\DomainBundle\Document\Guest\Guest')
            ->getQuery()
            ->execute();

        if ($guests) {
            $egoi = $this->em
                ->getRepository('DomainBundle:ApiEgoi')
                ->findOneBy([ 'token' => $listToken ]);

            if (!$egoi) {
                throw new TokenNotFoundException(
                    "Token do E-goi {$listToken} não foi encontrado."
                );
            }

            foreach ($guests as $guest) {
                $this->subscribe($egoi, $guest);
            }
        } else {
            throw new GuestNotFoundException('Não há visitantes para enviar ao E-goi.');
        }
    }

    private function trySendingSQSMessage($message) {
        try {
            $this->sqs->sendMessage($message);
        } catch (\Exception $exception) {
            $this->logger->addCritical("SQS Message error: {$exception->getMessage()}");
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
}
