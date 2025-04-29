<?php

namespace Wideti\DomainBundle\Service\Segmentation\Filter;

class Filter
{
    const TYPE_ALL = 'ALL';
    const TYPE_ANY = 'ANY';

    private $type;
    private $count;
    private $ids = [];
    private $client;

    /**
     * @var FilterItem
     */
    private $items;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param mixed $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return mixed
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @param array $ids
     * @param string $type
     */
    public function addIds($ids = [], $type = Filter::TYPE_ALL)
    {
        if ($type == Filter::TYPE_ALL) {
            $this->ids = [];
        }

        foreach ($ids as $id) {
            $this->setIds($id);
        }
    }

    /**
     * @param mixed $ids
     */
    public function setIds($ids)
    {
        $this->ids[] = $ids;

        if ($ids == null) {
            $this->ids = [];
        }
    }

    /**
     * @return FilterItem
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return FilterItem
     */
    public function getItem($identifier)
    {
        $items = $this->getItems();

        /**
         * @var FilterItem $item
         */
        foreach ($items as $item) {
            if ($item->getIdentifier() == $identifier) return $item;
        }

        return $this->items;
    }

    /**
     * @param FilterItem $item
     */
    public function addItem(FilterItem $item)
    {
        $this->setItems($item);
    }

    /**
     * @param FilterItem $items
     */
    public function setItems($items)
    {
        $this->items[] = $items;
    }

    public function hasItem($identifier)
    {
        $items = $this->getItems();

        /**
         * @var FilterItem $item
         */
        foreach ($items as $item) {
            if ($item->getIdentifier() == $identifier) return true;
        }

        return false;
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
}
