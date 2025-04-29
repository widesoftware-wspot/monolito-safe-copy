<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 08/06/18
 * Time: 14:37
 */

namespace Wideti\DomainBundle\Repository\Elasticsearch\Radacct;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

class AccountingStreamRepositoryImpTest extends WebTestCase
{

    public function testMustCallOnceSearchScrollIfNextTokenIsEmpty()
    {
        $elasticService = $this->getElasticSearchMock([
            'searchScroll' => 1,
            'scroll' => 0
        ]);

        $service = new AccountingStreamRepositoryImp($elasticService);
        $filter = new AcctStreamFilterDto();
        $filter->setClient(Client::createClientWithId(1));
        $service->findByFilter($filter);
    }

    public function testMustCallOnceScrollIfNextTokenExists()
    {
        $elasticService = $this->getElasticSearchMock([
            'searchScroll' => 0,
            'scroll' => 1
        ]);

        $service = new AccountingStreamRepositoryImp($elasticService);
        $filter = new AcctStreamFilterDto();
        $filter
            ->setClient(Client::createClientWithId(1))
            ->setNextToken("GHAJWUUYY&&*AKJHSKGD");
        $service->findByFilter($filter);
    }

    /**
     * @param array $timesExecute
     * @return ElasticSearch
     */
    private function getElasticSearchMock(array $timesExecute)
    {
        $mock = $this
            ->getMockBuilder('Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch')
            ->disableOriginalConstructor()
            ->setMethods(['searchScroll', 'scroll'])
            ->getMock();

        $mock
            ->expects($this->exactly($timesExecute['searchScroll']))
            ->method('searchScroll')
            ->will($this->returnValue([]));

        $mock
            ->expects($this->exactly($timesExecute['scroll']))
            ->method('scroll')
            ->will($this->returnValue([]));

        /** @var ElasticSearch $mock */
        return $mock;
    }

}
