<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Radacct;

use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

class QueryStreamAcctHelper
{
    const MAX_RESULT_SIZE_WINDOW = 100;
    const SCROLL_TIME_OPEN_WINDOW = '60s';

    /**
     * @param AcctStreamFilterDto $filter
     * @return array
     * @throws ClientNotFoundException
     */
    public static function createQuery(AcctStreamFilterDto $filter)
    {

        if (!$filter->getClient()) {
            throw new ClientNotFoundException("Cliente não existe ao criar query de acesso na API.");
        }

        $from = self::getDateOrDefaultFrom($filter);
        $to = self::getDateOrDefaultTo($filter);

        $params = [
            "scroll"    => self::SCROLL_TIME_OPEN_WINDOW,
            "size"      => self::MAX_RESULT_SIZE_WINDOW,
            "index"     => self::getIndexesToFind($filter),
            "type"      => ElasticSearch::TYPE_RADACCT,
            "ignore_unavailable" => true,
            "body"      => [
                "query" => [
                    "bool" => [
                        "must" => []
                    ]
                ]
            ]
        ];

        $filter->getIdentifier()
            && $params['body']['query']['bool']['must'][] = [
                "term" => ["calledstationid" => $filter->getIdentifier()]
            ];

        $filter->getGuest()
            && $params['body']['query']['bool']['must'][] = [
                "term" => ["username" => $filter->getGuest()]
            ];

        $params['body']['query']['bool']['must'][] = [
            "term" => ["client_id" => $filter->getClient()->getId()]
        ];

        $sortType = $filter->getOrder()
            ? $filter->getOrder()
            : 'desc';

        $params['body']['sort']['acctstarttime']['order'] = $sortType;

        $params['body']['query']['bool']['must'][] = [
            "range" => [
                "acctstarttime" => [
                    "gte" => $from->format('Y-m-d H:i:s'),
                    "lte" => $to->format('Y-m-d H:i:s')
                ]
            ]
        ];

        return $params;
    }

    /**
     * @param AcctStreamFilterDto $filter
     * @return string
     * @throws \Exception
     */
    public static function getIndexesToFind(AcctStreamFilterDto $filter)
    {
        $indexes = [];
        $from    = self::getDateOrDefaultFrom($filter);
        $to      = self::getDateOrDefaultTo($filter);

        if ($from->getTimestamp() > $to->getTimestamp()) {
            return ElasticSearch::CURRENT;
        }

        $from->modify('first day of this month');

        while (($from->format('Y') <= $to->format('Y')) &&
            ($from->format('Y') == $to->format('Y') ? $from->format('m') <= $to->format('m') : 1))
        {
            $indexes[] = $from->format('\w\s\p\o\t_Y_m');
            $from->add(new \DateInterval("P1M"));
        }

        return join(",", $indexes);
    }

    /**
     * @param AcctStreamFilterDto $filter
     * @return \DateTime
     * @throws \Exception
     */
    public static function getDateOrDefaultFrom(AcctStreamFilterDto $filter)
    {
        if ($filter->getFrom()) {
            return $filter->getFrom();
        }

        $defaultDate = new \DateTime();
        $defaultDate->sub(new \DateInterval('P30D'));
        $defaultDate->setTime(0,0,0);

        return $defaultDate;
    }

    /**
     * @param AcctStreamFilterDto $filter
     * @return \DateTime
     */
    public static function getDateOrDefaultTo(AcctStreamFilterDto $filter)
    {
        if ($filter->getTo()) return $filter->getTo();

        $defaultDate = new \DateTime();
        $defaultDate->setTime(23, 59, 59);

        return $defaultDate;
    }
}