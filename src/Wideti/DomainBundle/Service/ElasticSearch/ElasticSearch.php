<?php
namespace Wideti\DomainBundle\Service\ElasticSearch;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Wideti\DomainBundle\Helpers\ElasticSearchIndexHelper;

class ElasticSearch
{
    const TYPE_RADACCT = 'radacct';
    const TYPE_REPORTS = 'report';

    const CURRENT           = 'current';
    const LAST_MONTH        = 'last_month';
    const LAST_30_DAYS      = 'last_30_days';
    const LAST_3_MONTHS     = 'last_3_months';
    const LAST_6_MONTHS     = 'last_6_months';
    const LAST_12_MONTHS    = 'last_12_months';
    const ALL               = 'all';
    const ACCESS_CODE       = 'wspotv3';
    const LOG               = 'log';
    const FACEBOOK_CHECKINS = 'checkins';

    const TYPE_ERP_CHANGELOG    = 'erp_logs';
    const ACCT_IP_HISTORIC_ALL  = 'acct_ip_historic_all';

    const REPORT_VISITS_REGISTRATIONS_PER_HOUR      = 'report_visits_registrations_per_hour';
    const REPORT_VISITS_REGISTRATION_PER_HOUR_ALIAS = 'report_visits_registrations_per_hour_all';

    const REPORT_DOWNLOAD_UPLOAD        = 'report_download_upload';
    const REPORT_DOWNLOAD_UPLOAD_ALIAS  = 'report_download_upload_all';

    const REPORT_GUESTS        = 'report_guests';
    const REPORT_GUESTS_ALIAS  = 'report_guests_all';

    const NUMBER_OF_RETRIES = 3;

    protected $hosts;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;

    public function __construct($hosts)
    {
        $this->hosts = $hosts;

        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries(self::NUMBER_OF_RETRIES)
            ->build()
        ;
    }

    /**
     * Perform search on specific type
     *
     * @param string $type
     * @param $data
     *
     * @return array
     */
    public function search($type, $data, $index = null, $filter_path = null)
    {
        if ($index === null) {
            $index = ElasticSearch::CURRENT;
        }
        $params = [
            'index' => $index,
            'type'  => $type,
            'ignore_unavailable' => true,
            'body'  => $data,
            'filter_path' => $filter_path
        ];
        return $this->client->search($params);
    }


    public function searchScroll($params)
    {
        return $this->client->search($params);
    }

    public function scroll($params)
    {
        return $this->client->scroll($params);
    }

    /**
     * Prepare to get to a specific type in predetermined index
     *
     * @param string $type
     * @param string $id
     * @param object $class
     *
     * @return null|object
     * @throws \Exception if $class is not an object
     */
    public function find($type, $id, $class)
    {
        if (!is_object($class)) {
            throw new \InvalidArgumentException('Expected class '. gettype($class) . ' given');
        }
        return $this->get($type, $id, $class);
    }

    public function msearch($params)
    {
        $params = [
            'index' => $params['index'],
            'type'  => $params['type'],
            'body'  => $params['body']
        ];

        echo json_encode($params);
        return $this->client->msearch($params);
    }

    /**
     * Perform the bulk action on ElasticSearch
     *
     * @param string $type
     * @param array $params
     *
     * @return Response
     */
    public function bulk($type, $params = array(), $index = null)
    {
        if ($index === null) {
            $index = ElasticSearch::CURRENT;
        }

        $objectToIndex = [
            'index' => $index,
            'type'  => $type,
            'body'  => $params
        ];

        echo json_encode($objectToIndex);
        return new Response($this->client->bulk($objectToIndex));
    }

    /**
     * Perform the deleteByQuery action on ElasticSearch
     *
     * @param array $params
     *
     * @return Response
     */
    public function deleteByQuery($type, $params = array())
    {
        $params = [
            'index' => ElasticSearchIndexHelper::getIndex(),
            'type'  => $type,
            'body'  => $params
        ];

        echo json_encode($params);
        return new Response($this->client->deleteByQuery($params));
    }

    /**
     * Perform the update action on ElasticSearch
     *
     * @param string $type
     * @param $id
     * @param array $params
     *
     * @param null $index
     * @return Response
     */
    public function update($type, $id, $params = [], $index = null)
    {
        if ($index === null) {
            $index = ElasticSearch::CURRENT;
        }

        $objectToIndex = [
            'id'    => $id,
            'index' => $index,
            'type'  => $type,
            'body'  => $params
        ];

        try {
            echo json_encode($objectToIndex);
            $response = $this->client->update($objectToIndex);
        } catch (\Exception $ex) {
            if (get_class($ex) == Conflict409Exception::class) {
                $response = $this->forceUpdateVersionConflict($type, $index, $id);
            }
        }

        return new Response($response);
    }

    private function forceUpdateVersionConflict($type, $index, $id)
    {
        $body = [
            'query' => [
                'match' => [
                    '_id' => $id
                ]
            ]
        ];

        $search = $this->search($type, $body, $index);
        $object = $search['hits']['hits'][0]['_source'];
        $object['acctstoptime']     = date("Y-m-d H:i:s", strtotime("-1 second"));
        $object['interim_update']   = date("Y-m-d H:i:s", strtotime("-1 second"));

        $objectToIndex = [
            'id'    => $id,
            'index' => $index,
            'type'  => $type,
            'body'  => $object
        ];

        return $this->client->index($objectToIndex);
    }

    /**
     * @param $type
     * @param array $params
     * @param null $index
     * @return array
     */
    public function updateByQuery($type, $params = [], $index = null)
    {
        if ($index === null) {
            $index = ElasticSearch::CURRENT;
        }

        $objectToIndex = [
            'index' => $index,
            'type'  => $type,
            'body'  => $params,
            'conflicts' => 'proceed'
        ];

        echo json_encode($objectToIndex);
        return $this->client->updateByQuery($objectToIndex);
    }

    /**
     *
     * @param string $type
     * @param string $id
     * @param object $class
     *
     * @return null|object
     */
    private function get($type, $id, $class)
    {
        $response = $this->client->get([
            'index' => ElasticSearchIndexHelper::getIndex(),
            'type'  => $type,
            'id'    => $id
        ]);

        if ($response['found'] === false) {
            return null;
        }

        
        $object = $this->parseSource($response['_source'], $class);
        $object->setId($id);

        return $object;
    }

    /**
     * Parse array return to a given Object
     *
     * @param array $data
     * @param object $object
     *
     * @return object
     */
    private function parseSource($data, $object)
    {
        foreach ($data as $property => $value) {
            $method = sprintf('set%s', ucwords($property));
            $object->$method($value);
        }
        return $object;
    }

    /**
     * @param $type
     * @param $object
     * @return Response
     */
    public function save($type, $object)
    {
        $json = $this->serialize($object);
        $id   = $object->getId();

        echo json_encode($object);
        return $this->index($type, $json, $id);
    }

    /**
     * Perform the index action on ElasticSearch
     *
     * @param string $type
     * @param string $json
     * @param string $id (can be null in case of insert)
     * @param string $index
     *
     * @return Response
     */
    public function index($type, $json, $id = null, $index)
    {
        if ($index === null) {
            $index = ElasticSearch::CURRENT;
        }

        $objectToIndex = [
            'index' => $index,
            'type'  => $type,
            'body'  => $json
        ];

        if ($id !== null) {
            $objectToIndex['id'] = $id;
        }

        return new Response($this->client->index($objectToIndex));
    }

    public function post($index, $type, array $data)
    {
        $objectToIndex = [
            'index' => $index,
            'type'  => $type,
            'body'  => $data
        ];

        echo json_encode($objectToIndex);
        return new Response($this->client->index($objectToIndex));
    }

    /**
     * Parse given object to a valid JSON string
     *
     * @param object $object
     * @return string json
     */
    private function serialize($object)
    {
        $json = $this->serializer->serialize($object, 'json');

        if (!is_object(json_decode($json))) {
            throw new \InvalidArgumentException("Failed to parse object to json. Result: " . $json);
        }
        return $json;
    }

    /**
     * Inject Serializer
     *
     * @param \JMS\Serializer\Serializer $serializer
     */
    public function setSerializer(\JMS\Serializer\Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Get elasticsearch client
     *
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function getAliases()
    {
        $aliases = [
            ElasticSearch::CURRENT,
            ElasticSearch::LAST_MONTH,
            ElasticSearch::LAST_3_MONTHS,
            ElasticSearch::LAST_6_MONTHS,
            ElasticSearch::LAST_12_MONTHS,
            ElasticSearch::ALL
        ];

        return $aliases;
    }

    public function indices()
    {
        return $this->client->indices();
    }

    public function searchByScrollId($scrollId, $scrollTimeout)
    {
        $params = [
            'body' => [
                'scroll_id' => $scrollId,
                'scroll' => $scrollTimeout,
            ],
        ];

        return $this->client->scroll($params);
    }

        /**
     * Perform search on specific type
     *
     * @param string $type
     * @param $data
     *
     * @return array
     */
    public function searchScript($type, $data, $index = null, $filter_path = null,$scrollTimeout = '10m')
    {
        if ($index === null) {
            $index = ElasticSearch::CURRENT;
        }

        $params = [
            'index' => $index,
            'type'  => $type,
            'ignore_unavailable' => true,
            'body'  => $data,
            'scroll' => $scrollTimeout,
            'filter_path' => $filter_path
        ];

        return $this->client->search($params);
    }

}
