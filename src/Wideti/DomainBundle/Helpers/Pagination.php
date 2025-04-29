<?php

namespace Wideti\DomainBundle\Helpers;

class Pagination
{
    private $page;

    private $total;

    private $perPage;

    public function __construct($page, $total, $perPage = null)
    {
        $this->setPage($page);
        $this->setTotal($total);
        $this->setPerPage($perPage);
    }

    private function setPage($page)
    {
        $this->page = $page;
    }

    private function setTotal($total)
    {
        $this->total = $total;
    }

    private function setPerPage($perPage)
    {
        if ($perPage === null) {
            $this->perPage = 10;
        } else {
            $this->perPage = $perPage;
        }
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }

    private function getPreviousPage()
    {
        $previous = $this->page > 1 ? $this->page - 1 : 1;

        return $previous;
    }

    private function getLastPage()
    {
        $last = ceil($this->total / $this->perPage);

        return $last;
    }

    private function getNextPage()
    {
        $next = $this->page < $this->getLastPage()
                    ? $this->page + 1
                    : $this->getLastPage();

        return $next;
    }

    private function getOffSet()
    {
        $offSet = ($this->page - 1) * $this->perPage;

        return $offSet;
    }

    private function generatePages()
    {
        $start = $this->getPage() - 3;
        $end   = $this->getPage() + 3;

        if ($start <= 0) {
            $start = 1;
        }

        if ($end >= $this->getLastPage()) {
            $end = $this->getLastPage();
        }
        $index = 0;

        $pages = array();

        for ($i = $start; $i <= $end; $i++) {
            $pages[$index] = $i;
            $index++;
        }
        //\print_r($pages);
        return $pages;
    }

    public function createPagination()
    {
        $pagination['lastPage']      = $this->getLastPage();
        $pagination['previousPage']  = $this->getPreviousPage();
        $pagination['nextPage']      = $this->getNextPage();
        $pagination['offset']        = $this->getOffSet();
        $pagination['currentPage']   = $this->getPage();
        $pagination['total']         = $this->getTotal();
        $pagination['pages']         = $this->generatePages();

        return $pagination;
    }
}
