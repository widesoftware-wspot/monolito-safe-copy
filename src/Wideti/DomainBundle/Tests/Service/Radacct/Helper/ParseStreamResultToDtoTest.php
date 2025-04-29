<?php

namespace Wideti\DomainBundle\Service\Radacct\Helper;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamDto;

class ParseStreamResultToDtoHelperTest extends WebTestCase
{
    public function testMustParseResultInDtoArray()
    {
        $result = ParseStreamResultToDtoHelper::parse($this->getMockStreamResult());
        $this->assertInstanceOf(AcctStreamDto::class, $result);
        $this->assertEquals('cXVlcnlUaGVuRmV0Y2g7Mjs5OTE6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTs5OTI6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTswOw==', $result->getNextToken());
        $this->assertEquals(2, $result->getTotalRegistries());
        $this->assertEquals('5ab25c6133d74', $result->getData()[0]->getId());
        $this->assertEquals('2B-9C-E8-F4-4D-75', $result->getData()[0]->getIdentifier());
        $this->assertEquals(2, $result->getData()[0]->getGuest());
        $this->assertEquals(22621931, $result->getData()[0]->getAcctInputOctets());
        $this->assertEquals(22621931, $result->getData()[0]->getDownload());
        $this->assertEquals(29343320, $result->getData()[0]->getAcctOutputOctets());
        $this->assertEquals(29343320, $result->getData()[0]->getUpload());
        $this->assertEquals('5B-19-B1-37-0D-37', $result->getData()[0]->getGuestDevice());
        $this->assertEquals('192.168.100.253', $result->getData()[0]->getGuestIp());
        $this->assertEquals(true, $result->getData()[0]->isEmployee());
        $this->assertEquals('192.168.150.251', $result->getData()[0]->getNasIpAddress());
        $this->assertEquals('2018-02-01 00:04:46', $result->getData()[0]->getStart());
        $this->assertEquals('2018-02-01 01:59:46', $result->getData()[0]->getStop());
    }

    public function testMustParseResultEmployeeFalseIfEmployeeNotExists()
    {
        $result = ParseStreamResultToDtoHelper::parse($this->getMockStreamResult());
        $this->assertEquals(false, $result->getData()[1]->isEmployee());
    }

    public function testMustReturnDataEmptyIfResultIsEmpty()
    {
        $result = ParseStreamResultToDtoHelper::parse($this->getMockStreamEmpty());
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(null, $result->getNextToken());
    }

    public function testMustThrowInvalidArgumentExceptionIfArgumentIsInvalid()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        ParseStreamResultToDtoHelper::parse([]);
    }

    private function getMockStreamEmpty()
    {
        return [
            "_scroll_id" => "cXVlcnlUaGVuRmV0Y2g7Mjs5OTE6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTs5OTI6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTswOw==",
            "took" => 1,
            "timed_out" => false,
            "_shards" => [
                "total" => 2,
                "successful" => 2,
                "failed" => 0,
            ],
            "hits" => [
                "total" => 0,
                "max_score" => null,
                "hits" => [],
                ]
            ];
    }

    private function getMockStreamResult()
    {
        return [
            "_scroll_id" => "cXVlcnlUaGVuRmV0Y2g7Mjs5OTE6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTs5OTI6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTswOw==",
            "took" => 1,
            "timed_out" => false,
            "_shards" => [
                "total" => 2,
                "successful" => 2,
                "failed" => 0,
            ],
            "hits" => [
                "total" => 2,
                "max_score" => null,
                "hits" => [
                    0 => [
                        "_index" => "wspot_2018_02",
                        "_type" => "radacct",
                        "_id" => "5ab25c6133d74",
                        "_score" => null,
                        "_source" => [
                            "acctuniqueid" => "5ab25c6133db8",
                            "calledstationid" => "2B-9C-E8-F4-4D-75",
                            "framedipaddress" => "192.168.100.253",
                            "acctstarttime" => "2018-02-01 00:04:46",
                            "callingstationid" => "5B-19-B1-37-0D-37",
                            "nasipaddress" => "192.168.150.251",
                            "acctstoptime" => "2018-02-01 01:59:46",
                            "employee" => true,
                            "client_id" => 1,
                            "acctoutputoctets" => 29343320,
                            "upload" => 29343320,
                            "calledstation_name" => "AP Teste 02",
                            "acctsessionid" => 5218827194,
                            "acctinputoctets" => 22621931,
                            "download" => 22621931,
                            "id" => "5ab25c6133d74",
                            "interim_update" => "2018-02-01 01:59:46",
                            "username" => 2,
                        ],
                        "sort" => [
                            0 => 1517443486000
                        ]
                    ],
                    1 => [
                        "_index" => "wspot_2018_02",
                        "_type" => "radacct",
                        "_id" => "5ab2987a30651",
                        "_score" => null,
                        "_source" => [
                            "acctuniqueid" => "5ab2987a30695",
                            "calledstationid" => "11-11-11-11-11-11",
                            "framedipaddress" => "192.168.100.253",
                            "acctstarttime" => "2018-02-01 00:02:37",
                            "callingstationid" => "8A-43-2B-8D-A8-C1",
                            "nasipaddress" => "192.168.150.251",
                            "acctstoptime" => "2018-02-01 00:30:37",
                            "client_id" => 1,
                            "acctoutputoctets" => 12789911,
                            "calledstation_name" => "AP Teste 01",
                            "acctsessionid" => 760176123,
                            "acctinputoctets" => 5414786,
                            "id" => "5ab2987a30651",
                            "interim_update" => "2018-02-01 00:30:37",
                            "username" => 2,
                        ],
                        "sort" => [
                            0 => 1517443357000
                        ]
                    ]
                ]
            ]
        ];
    }
}
