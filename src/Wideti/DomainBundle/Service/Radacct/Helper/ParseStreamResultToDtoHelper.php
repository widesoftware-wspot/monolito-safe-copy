<?php

namespace Wideti\DomainBundle\Service\Radacct\Helper;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\QueryStreamAcctHelper;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctDataDto;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamDto;

class ParseStreamResultToDtoHelper extends WebTestCase
{
    /**
     * @param array $data
     * @return AcctStreamDto
     */
    public static function parse(array $data)
    {
        if (empty($data) || !isset($data['hits'])) {
            throw new \InvalidArgumentException("Erro ao geras os resultados.");
        }


        $streamDto = new AcctStreamDto();
        $streamDto
            ->setTotalRegistries($data['hits']['total']);

        if (
            isset($data['_scroll_id'])
            && $streamDto->getTotalRegistries() > 0
        ) {
            $streamDto->setNextToken($data['_scroll_id']);
        }

        foreach ($data['hits']['hits'] as $result) {
            $info = $result['_source'];
            $acctData = new AcctDataDto();
            $acctData
                ->setId($result['_id'])
                ->setIdentifier($info['calledstationid'])
                ->setGuest($info["username"])
                ->setFriendlyName($info["calledstation_name"])
                ->setAcctInputOctets($info["acctinputoctets"])
                ->setAcctOutputOctets($info["acctoutputoctets"])
                ->setUpload($info["upload"])
                ->setDownload($info["download"])
                ->setGuestDevice($info["callingstationid"])
                ->setGuestIp($info["framedipaddress"])
                ->setIsEmployee(isset($info["employee"])
                    ? $info["employee"]
                    : false)
                ->setNasIpAddress($info["nasipaddress"])
                ->setStart($info["acctstarttime"])
                ->setStop($info["acctstoptime"]);

            $streamDto->addData($acctData);
        }

        return $streamDto;
    }
}
