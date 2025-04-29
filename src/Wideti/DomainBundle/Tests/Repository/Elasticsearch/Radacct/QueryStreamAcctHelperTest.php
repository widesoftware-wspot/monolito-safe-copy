<?php

namespace Wideti\DomainBundle\Tests\Repository\Elasticsearch\Radacct;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\QueryStreamAcctHelper;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

class QueryStreamAcctHelperTest extends WebTestCase
{

    public function testMustReturnSameDateFromWhenFromExists()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setFrom(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-01 00:00:00"));

        $date = QueryStreamAcctHelper::getDateOrDefaultFrom($filter);

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2018-01-01 00:00:00', $date->format("Y-m-d H:i:s"));
    }

    public function testMustReturnDateFromLast30Days()
    {
        $filter = new AcctStreamFilterDto();

        $correctDate = new \DateTime();
        $correctDate->sub(new \DateInterval("P30D"));
        $correctDate->setTime(0,0,0);

        $date = QueryStreamAcctHelper::getDateOrDefaultFrom($filter);
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals($correctDate->format('Y-m-d H:i:s'), $date->format("Y-m-d H:i:s"));
    }

    public function testMustReturnSameDateToWhenToExists()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setTo(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-01 00:00:00"));

        $date = QueryStreamAcctHelper::getDateOrDefaultTo($filter);

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2018-01-01 00:00:00', $date->format("Y-m-d H:i:s"));
    }

    public function testMustReturnDefaultDateToWhenToIsNull()
    {
        $filter = new AcctStreamFilterDto();
        $correctDate = new \DateTime();
        $correctDate->setTime(23,59,59);

        $date = QueryStreamAcctHelper::getDateOrDefaultTo($filter);

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals($correctDate->format("Y-m-d H:i:s"), $date->format("Y-m-d H:i:s"));
    }

    public function testMustReturnIndexesToFindInElasticBasedInDatesFromAndTo()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setFrom(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-01 00:00:00"))
            ->setTo(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-05-20 23:59:59"));

        $indexes = QueryStreamAcctHelper::getIndexesToFind($filter);

        $this->assertEquals("wspot_2018_01,wspot_2018_02,wspot_2018_03,wspot_2018_04,wspot_2018_05", $indexes);
    }

    public function testMustReturnOnlyOneIndexBecauseDateInSameMonth()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setFrom(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-01 00:00:00"))
            ->setTo(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-20 23:59:59"));

        $indexes = QueryStreamAcctHelper::getIndexesToFind($filter);

        $this->assertEquals("wspot_2018_01", $indexes);
    }

    public function testMustReturnTwoIndexes()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setFrom(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-01 00:00:00"))
            ->setTo(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-02-20 23:59:59"));

        $indexes = QueryStreamAcctHelper::getIndexesToFind($filter);

        $this->assertEquals("wspot_2018_01,wspot_2018_02", $indexes);
    }

    public function testMustReturnIndexCurrentIfFromIsBiggerThanTo()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setFrom(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-08-01 00:00:00"))
            ->setTo(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-02-20 23:59:59"));

        $indexes = QueryStreamAcctHelper::getIndexesToFind($filter);

        $this->assertEquals("current", $indexes);
    }

    public function testMustReturnIndexesFollowingTheNextYear()
    {
        $filter = new AcctStreamFilterDto();
        $filter
            ->setFrom(\DateTime::createFromFormat("Y-m-d H:i:s", "2018-11-01 00:00:00"))
            ->setTo(\DateTime::createFromFormat("Y-m-d H:i:s", "2019-02-20 23:59:59"));

        $indexes = QueryStreamAcctHelper::getIndexesToFind($filter);

        $this->assertEquals("wspot_2018_11,wspot_2018_12,wspot_2019_01,wspot_2019_02", $indexes);
    }

    public function testMustCreateQueryWithEmptyFilter()
    {
        $assertFrom = new \DateTime();
        $assertTo = new \DateTime();
        $assertFrom->sub(new \DateInterval('P30D'));
        $assertFrom->setTime(0,0,0);
        $assertTo->setTime(23,59,59);
        $assertFilter = new AcctStreamFilterDto();
        $assertFilter->setFrom($assertFrom)->setTo($assertTo);

        $filter = new AcctStreamFilterDto();
        $filter
            ->setClient(Client::createClientWithId(1));

        $query = QueryStreamAcctHelper::createQuery($filter);

        $this->assertInternalType('array', $query);
        $this->assertEquals([
            "scroll"    => '60s',
            "size"      => 100,
            "index"     => QueryStreamAcctHelper::getIndexesToFind($assertFilter),
            "type"      => 'radacct',
            "ignore_unavailable" => true,
            "body"      => [
                "query" => [
                    "bool" => [
                        "must" => [
                            [ "term" => ["client_id" => 1 ] ],
                            [
                                "range" => [
                                    'acctstarttime' => [
                                        'gte' => $assertFrom->format("Y-m-d H:i:s"),
                                        'lte' => $assertTo->format("Y-m-d H:i:s")
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "sort" => ['acctstarttime' => ['order' => 'desc']]
            ]
        ], $query);
    }

    public function testMustCreateQueryWithAllFilters()
    {
        $from = \DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-01 00:00:00");
        $to = \DateTime::createFromFormat("Y-m-d H:i:s", "2018-01-20 23:59:59");

        $filter = new AcctStreamFilterDto();
        $filter
            ->setClient(Client::createClientWithId(1))
            ->setFrom($from)
            ->setTo($to)
            ->setOrder("asc")
            ->setGuest(33)
            ->setIdentifier("11-11-11-11-11");

        $query = QueryStreamAcctHelper::createQuery($filter);

        $this->assertInternalType('array', $query);
        $this->assertEquals([
            "scroll"    => '60s',
            "size"      => 100,
            "index"     => 'wspot_2018_01',
            "type"      => 'radacct',
            "ignore_unavailable" => true,
            "body"      => [
                "query" => [
                    "bool" => [
                        "must" => [
                            [ "term" => ["calledstationid" => '11-11-11-11-11' ] ],
                            [ "term" => ["username" => 33 ] ],
                            [ "term" => ["client_id" => 1 ] ],
                            [
                                "range" => [
                                    'acctstarttime' => [
                                        'gte' => "2018-01-01 00:00:00",
                                        'lte' => '2018-01-20 23:59:59'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "sort" => ['acctstarttime' => ['order' => 'asc']]
            ]
        ], $query);
    }

    public function testMustThrowClientNotFoundExceptionIfClientNotExists()
    {
        $this->setExpectedException(ClientNotFoundException::class);
        $filter = new AcctStreamFilterDto();
        QueryStreamAcctHelper::createQuery($filter);
    }
}