<?php

namespace Wideti\DomainBundle\Tests\Service\Radacct;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\AccountingStreamRepository;
use Wideti\DomainBundle\Service\Radacct\AccountingStreamServiceImp;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamDto;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

class AccountingStreamServiceImpTest extends WebTestCase
{
    public function testMustReturnAcctStreamDto()
    {
        $repository = $this->getAcctStreamRepositoryWithResult(1);

        $filter = new AcctStreamFilterDto();
        $service = new AccountingStreamServiceImp($repository);
        $stream = $service->get($filter);

        $this->assertInstanceOf(AcctStreamDto::class, $stream);
        $this->assertInternalType('array', $stream->getData());
        $this->assertCount(1, $stream->getData());
    }

    public function testMustThrowClientNotFountExceptionIfClientNotExistsInFilter()
    {
        $this->setExpectedException(ClientNotFoundException::class);
        $repository = $this->getAcctStreamRepositoryWithClientNotFoundException(1);

        $filter = new AcctStreamFilterDto();
        $service = new AccountingStreamServiceImp($repository);

        $stream = $service->get($filter);
    }

    /**
     * @param int $timesExecute
     * @return AccountingStreamRepository
     */
    private function getAcctStreamRepositoryWithResult($timesExecute = 0)
    {
        $mock = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\Elasticsearch\Radacct\AccountingStreamRepositoryImp')
            ->disableOriginalConstructor()
            ->setMethods(['findByFilter'])
            ->getMock();

        $mock
            ->expects($this->exactly($timesExecute))
            ->method('findByFilter')
            ->will($this->returnValue($this->getElasticResult()));
        /** @var AccountingStreamRepository $mock */
        return $mock;
    }

    /**
     * @param int $timesExecute
     * @return AccountingStreamRepository
     */
    private function getAcctStreamRepositoryWithClientNotFoundException($timesExecute = 0)
    {
        $mock = $this
            ->getMockBuilder('Wideti\DomainBundle\Repository\Elasticsearch\Radacct\AccountingStreamRepositoryImp')
            ->disableOriginalConstructor()
            ->setMethods(['findByFilter'])
            ->getMock();

        $mock
            ->expects($this->exactly($timesExecute))
            ->method('findByFilter')
            ->will($this->throwException(new ClientNotFoundException()));
        /** @var AccountingStreamRepository $mock */
        return $mock;
    }

    private function getElasticResult()
    {
        return [
            "_scroll_id" => "cXVlcnlUaGVuRmV0Y2g7NDs5MDE6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTs5MDQ6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTs5MDM6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTs5MDI6dVhEanBjRjRUcVNWd2xBWkpaLTdDUTswOw==",
            "took" => 5,
            "timed_out" => false,
            "_shards" => [
                "total" => 4,
                "successful" => 4,
                "failed" => 0,
            ],
            "hits" => [
                "total" => 19416,
                "max_score" => 0.99981433,
                "hits" => [
                    0 => [
                        "_index" => "wspot_2018_02",
                        "_type" => "radacct",
                        "_id" => "5ab2986f418ae",
                        "_score" => 0.99981433,
                        "_source" =>  [
                            "acctuniqueid" => "5ab2986f418ee",
                            "calledstationid" => "11-11-11-11-11-11",
                            "framedipaddress" => "192.168.100.253",
                            "acctstarttime" => "2018-02-07 20:04:17",
                            "callingstationid" => "10-EB-52-20-12-61",
                            "nasipaddress" => "192.168.150.251",
                            "acctstoptime" => "2018-02-07 21:57:17",
                            "employee" => true,
                            "client_id" => 1,
                            "acctoutputoctets" => 8890142,
                            "calledstation_name" => "AP Teste 01",
                            "acctsessionid" => 2687650560,
                            "acctinputoctets" => 22564111,
                            "id" => "5ab2986f418ae",
                            "interim_update" => "2018-02-07 21:57:17",
                            "username" => 1,
                        ]
                    ]
                ]
            ]
        ];
    }
}