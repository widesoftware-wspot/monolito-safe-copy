<?php

namespace Wideti\PanelBundle\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginationService
{
    /**
     * Number os items per Page
     * @var int
     */
    private $itemsNumber;

    /**
     * PaginationService constructor.
     * @param int $itemsNumber
     */
    public function __construct($itemsNumber)
    {
        $this->itemsNumber = $itemsNumber;
    }

    /**
     * Get the total of pages
     *
     * @param Paginator $paginator
     *
     * @return float
     */
    public function getTotalPages($paginator)
    {
        return ceil(count($paginator) / $this->itemsNumber);
    }

    /**
     * @param $query
     * @param $currentPage
     * @return Paginator
     */
    public function paginate($query, $currentPage)
    {
        $paginator = new Paginator($query);
        $offset = ($currentPage * $this->itemsNumber) - $this->itemsNumber;
        $paginator->getQuery()
            ->setFirstResult($offset) // Offset
            ->setMaxResults($this->itemsNumber); // Limit
        return $paginator;
    }

    /**
     * @param int $limitFilter
     * @return int
     */
    public function limitPageFilter($limitFilter)
    {
        if ($limitFilter == 0) {
            return $this->itemsNumber;
        }
        return $limitFilter;
    }
}