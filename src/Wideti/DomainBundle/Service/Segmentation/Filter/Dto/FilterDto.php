<?php

namespace Wideti\DomainBundle\Service\Segmentation\Filter\Dto;

use Symfony\Component\HttpFoundation\Request;

class FilterDto
{
    private $type;
    private $items;
    private $client;
    private $userId;

    /**
     * @param Request $request
     * @return FilterDto
     */
    public static function createFromRequest(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        $filter = new FilterDto();
        $filter
            ->setType($content['type'])
            ->setClient($content['client'])
        ;

        $filter->userId = $content['userId'];

        foreach ($content['items'] as $items) {
            foreach ($items as $key => $item) {
                $filterItem = new FilterItemDto();
                $filterItem
                    ->setIdentifier($item['identifier'])
                    ->setEquality($item['equality'])
                    ->setType($item['type'])
                    ->setValue($item['value'])
                ;

                $filter->addItem($filterItem);
            }
        }

        return $filter;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param mixed $items
     */
    public function addItem(FilterItemDto $items)
    {
        $this->items[] = $items;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    public function getUserId() {
        return $this->userId;
    }
}
