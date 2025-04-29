<?php
namespace Wideti\DomainBundle\Service\Watchdog;

use Doctrine\ORM\EntityManager;
use PDO;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Watchdog\Dto\VerifyAccessPoint;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MailerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class DevelopmentReport implements WatchdogServiceInterface
{
	use EntityManagerAware;
	use TwigAware;
	use MailerServiceAware;
	use MailerAware;
	use MailHeaderServiceAware;
	use ElasticSearchAware;
	use MongoAware;
	use LoggerAware;

	public function execute()
	{
		try {
			$countElasticSearch             = $this->totalElasticSearch();
			$startTimeLargerThanStopTime    = $this->startTimeLargerThanStopTime();
			$totalGuestsMySql               = $this->totalGuestsMySql($this->em->getConnection());
			$indexes                        = $this->checkAliases();
			$wrongApsInClient               = $this->verifyWrongAps();

			$totalGuests = [];

			$totalInvalidEmails = 0;

			foreach ($totalGuestsMySql as $mysql) {
				$connection     = $this->mongo->getConnection();
				$clientDatabase = $mysql["client"];
				$database       = $connection->selectDatabase($clientDatabase);
				$connection     = $database->guests;

				$mongoCount     = $connection->count();
				$mysqlCount     = $mysql['total'];

				$totalGuests[$mysql["client"]] = ['mysql' => (int) $mysqlCount, 'mongo' => (int) $mongoCount];

				$guests = $connection->find([
					"emailIsValid" => false
				]);

				foreach ($guests as $guest) {
					$totalInvalidEmails++;
				}
			}

			$this->send([
				'deliveryTo'            => $this->emailHeader->getDevelopersRecipient(),
				'countES'               => $countElasticSearch['hits']['total'],
				'startTime'             => $startTimeLargerThanStopTime['hits']['total'],
				'totalGuests'           => $totalGuests,
				'totalInvalidEmails'    => $totalInvalidEmails,
				'indexes'               => $indexes,
				'wrongAps'              => $wrongApsInClient
			]);
		} catch (\Exception $ex) {
			$this->logger->addCritical("Watchdog failed - " . $ex->getMessage());
		}
	}

	public function send($params = [])
	{
		$builder = new MailMessageBuilder();
		$message = $builder
			->subject('[WATCHDOG] Relatorios')
			->from(['WSpot' => $this->emailHeader->getSender()])
			->to($params['deliveryTo'])
			->htmlMessage(
				$this->renderView(
					'AdminBundle:Admin:watchdog.html.twig',
					[
						'countElasticSearch'    => $params['countES'],
						'startTime'             => $params['startTime'],
						'opened'                => isset($params['opened']) ? $params['opened'] : 0,
						'totalGuests'           => $params['totalGuests'],
						'totalInvalidEmails'    => $params['totalInvalidEmails'],
						'indexes'               => $params['indexes'],
						'wrongAps'              => $params['wrongAps']
					]
				)
			)
			->build()
		;

		$this->mailerService->send($message);
	}

	private function totalElasticSearch()
	{
		return $this->elasticSearchService->search(
			'radacct',
			[
				"size" => 0,
				"query" => [
					"match_all" => []
				]
			],
			ElasticSearch::ALL
		);
	}

	private function startTimeLargerThanStopTime()
	{
		return $this->elasticSearchService->search(
			'radacct',
			[
				"size" => 0,
				"query" => [
					"filtered" => [
						"filter" => [
							"script" => [
								"script" => "doc['acctstarttime'].value > doc['acctstoptime'].value"
							]
						]
					]
				]
			],
			ElasticSearch::ALL
		);
	}

	private function totalGuestsMySql($connection)
	{
		$statement = $connection->prepare("
            SELECT c.domain as client, COUNT(v.id) as total
            FROM visitantes v
            INNER JOIN clients c ON v.client_id = c.id
            GROUP BY v.client_id
        ");
		$statement->execute();

		return $statement->fetchAll();
	}

	private function checkAliases()
	{
		$aliases = $this->elasticSearchService->indices();
		$alias   = $aliases->getAlias([]);

		$indexes = [];

		ksort($alias);

		foreach ($alias as $key => $value) {
			if (!empty($value['aliases'])) {
				$indexes[$key] = implode(' | ', array_keys($value['aliases']));
			}
		}

		return $indexes;
	}

	/**
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function verifyWrongAps()
	{
		$clients = $this->em
			->getRepository('DomainBundle:Client')
			->findAll();

		$apChecks = [];
		foreach ($clients as $client) {
			$apChecks[] = $this->checkApsFromClient($client);
		}

		return $apChecks;
	}

	/**
	 * @param Client $client
	 * @return VerifyAccessPoint
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function checkApsFromClient(Client $client)
	{
		$apsMacMysql = $this->getClientAccessPointsIdentifier($client);
		$apsMacMongo = $this->getMongoRegistredMacAddress($client);
		$apsMacElastic = $this->getCalledStationidElastic($client);

		$verify = new VerifyAccessPoint($client);
		foreach ($apsMacMongo as $mac) {
			if (!in_array($mac, $apsMacMysql)) {
				$verify->addMongoWrongMac($mac);
			}
		}

		foreach ($apsMacElastic as $mac) {
			if (!in_array($mac, $apsMacMysql)) {
				$verify->addElasticWrongMac($mac);
			}
		}
		return $verify;
	}

	/**
	 * @param Client $client
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getClientAccessPointsIdentifier(Client $client)
	{
		$clientId = $client->getId();
		$con = $this->em->getConnection();
		$stmt = $con->prepare("select identifier from access_points where client_id = :clientId");
		$stmt->bindParam('clientId', $clientId, \PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN);
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @param Client $client
	 * @return string[]
	 */
	private function getMongoRegistredMacAddress(Client $client)
	{
		$con = $this->mongo->getConnection();
		$db = $con->selectDatabase($client->getDomain());
		$collection = $db->selectCollection('guests');

		$result = $collection->aggregate([
			'$group' => [
				'_id' => '$registrationMacAddress',
				'count' => ['$sum' => 1]
			]
		]);

		$mongoMacs = [];
		foreach ($result as $mac) {
			(!empty($mac) && $mongoMacs[] = trim($mac['_id']));
		}
		return $mongoMacs;
	}

	/**
	 * @param Client $client
	 * @return string[]
	 */
	private function getCalledStationidElastic(Client $client)
	{
		$query = [
			"size" => 0,
			"query" => [
				"term" => [
					"client_id" => $client->getId()
				]
			],
			"aggs" => [
				"aps" => [
					"terms" => [
						"field" => "calledstationid",
						"size" => 10000
					]
				]
			]
		];

		$result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::CURRENT);
		$elasticMacs = [];
		foreach ($result['aggregations']['aps']['buckets'] as $acct) {
			$mac = $this->prepareCalledStationId($acct['key']);
			(!empty($mac) && $elasticMacs[] = trim($mac));
		}
		return $elasticMacs;
	}

	/**
	 * @param string $identifier
	 * @return null | string
	 */
	private function prepareCalledStationId($identifier)
	{
		if (empty($identifier)) {
			return null;
		}

		$newIdentifier = explode(":", $identifier)[0];

		$isIp = preg_match('/\b(?:(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]))\b/', $newIdentifier);

		$isMac = preg_match('/((?:[a-zA-Z0-9]{2}[:-]){5}[a-zA-Z0-9]{2})/', $newIdentifier);

		if ($isMac || $isIp) {
			return $newIdentifier;
		}

		return $identifier;
	}
}
