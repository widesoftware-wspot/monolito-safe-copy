<?php

namespace Wideti\DomainBundle\Service\Radacct;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\WifiMode;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class RadacctService
{
    use RadacctRepositoryAware;

    /**
     * @var WifiMode
     */
    private $wifiMode;

    public function __construct(WifiMode $wifiMode)
    {
        $this->wifiMode = $wifiMode;
    }

    public function getTotalDownloadUploadByGuest(Guest $guest)
    {
        $downloadField  = 'download';
        $uploadField    = 'upload';

        return $this->radacctRepository->getDownloadUploadByGuest($guest, $downloadField, $uploadField);
    }

    public function getClosedAccountingsByGuest(Guest $guest, $order = "desc", $limit = 1000)
    {
        $closedAccess = $this->radacctRepository->getClosedAccountingsByGuest($guest, $order, $limit);

        $accountings  = [];

        if ($closedAccess) {
            foreach ($closedAccess as $row) {
                $newRow       = $row["_source"];
                $newRow["id"] = $row["_id"];
                $accountings[] = $newRow;
                unset($newRow);
            }
        }

        return $accountings;
    }

    public function getAverageTimeAccessByGuest(Guest $guest)
    {
        $averageTimeAccess = $this->radacctRepository->getAverageTimeAccessByGuest($guest);

        if (!isset($averageTimeAccess['aggregations'])) return 0;

        $totalSeconds   = $averageTimeAccess['aggregations']['total_access_time_in_seconds']['value'];
        $averageSeconds = ($averageTimeAccess['hits']['total'] == 0) ? 1 : $averageTimeAccess['hits']['total'];
        $averageTime    = (abs(intval(substr($totalSeconds, 0, -3) / $averageSeconds)));

        return $averageTime;
    }

    public function getAccessByUniqueId($acctUniqueId, $clientId)
    {
        return $this->radacctRepository->findByAcctUniqueId($acctUniqueId, $clientId);
    }

    public function getLastAccountingByApMacaddress(Client $client, $macaddress, $period = "sempre")
    {
        $lastAccounting = $this->radacctRepository->getLastAccountingByApMacaddress($client, $macaddress, $period);

        if (count($lastAccounting["hits"]["hits"]) == 0) {
            return false;
        }

        return $lastAccounting["hits"]["hits"][0]["_source"];
    }

    public function searchDuplicatedOpenedSessions()
    {
        $results = $this->radacctRepository->searchDuplicatedOpenedSessions();

        if (isset($results['_shards']['failures']) && count($results['_shards']['failures']) > 0) {
            throw new \Exception($results['_shards']['failures'][0]['reason']['reason']);
        }

        return $results['hits']['hits'];
    }

    public function updateCloseAccounting($accounting, $index = null)
    {
        return $this->radacctRepository->updateCloseAccounting($accounting, $index);
    }

    public function updateAcctStopTimeSubtractingOneSecond($currentId, $nextAcctstarttime, $index = null)
    {
        return $this->radacctRepository->updateAcctStopTimeSubtractingOneSecond($currentId, $nextAcctstarttime, $index);
    }

    public function getAccessByGuest(Guest $guest)
    {
        return $this->radacctRepository->getTotalAccessByGuest($guest);
    }

    /**
     * @param int $id
     * @return boolean
     */
    public function isGuestOnline($id)
    {
        return $this->radacctRepository->isGuestOnline($id);
    }

    public function getAcctIpHistoric($acctuniqueid)
    {
        return $this->radacctRepository->getAcctIpHistoric($acctuniqueid);
    }
}
