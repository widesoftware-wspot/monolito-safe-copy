<?php

namespace Wideti\DomainBundle\Dto;


class RestPaginateDto implements \JsonSerializable
{
    private $page;
    private $totalOfPages;
    private $totalOfElements;
    private $limitPerPage;
    private $order;
    private $elements;
    private $nextLink;
    private $previusLink;

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param mixed $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return mixed
     */
    public function getTotalOfElements()
    {
        return $this->totalOfElements;
    }

    /**
     * @param mixed $totalOfElements
     */
    public function setTotalOfElements($totalOfElements)
    {
        $this->totalOfElements = $totalOfElements;
    }

    /**
     * @return mixed
     */
    public function getLimitPerPage()
    {
        return $this->limitPerPage;
    }

    /**
     * @param mixed $limitPerPage
     */
    public function setLimitPerPage($limitPerPage)
    {
        $this->limitPerPage = $limitPerPage;
    }

    /**
     * @return mixed
     */
    public function getTotalOfPages()
    {
        return $this->totalOfPages;
    }

    /**
     * @param mixed $totalOfPages
     */
    public function setTotalOfPages($totalOfPages)
    {
        $this->totalOfPages = $totalOfPages;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return mixed
     */
    public function getNextLink()
    {
        return $this->nextLink;
    }

    /**
     * @param mixed $nextLink
     */
    public function setNextLink($nextLink)
    {
        $this->nextLink = $nextLink;
    }

    /**
     * @return mixed
     */
    public function getPreviusLink()
    {
        return $this->previusLink;
    }

    /**
     * @param mixed $previusLink
     */
    public function setPreviusLink($previusLink)
    {
        $this->previusLink = $previusLink;
    }

    /**
     * @return mixed
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @param mixed $elements
     */
    public function setElements($elements)
    {
        $this->elements = $elements;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}