<?php

namespace Wideti\PanelBundle\Helpers;

use Wideti\PanelBundle\Service\PaginationService;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaginationHelper
{
    /**
     * @var PaginationService
     */
    private $pagination;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * ControllerUtilsHelper constructor.
     * @param PaginationService $pagination
     * @param ContainerInterface $container
     */
    public function __construct(
        PaginationService $pagination,
        ContainerInterface $container
    ) {
        $this->pagination = $pagination;
        $this->container = $container;
    }

    /**
     * @return PaginationService
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string $url    The URL to redirect to
     * @param int    $status The status code to use for the Response
     *
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route      The name of the route
     * @param array  $parameters An array of parameters
     * @param int    $status     The status code to use for the Response
     *
     * @return RedirectResponse
     */
    public function redirectToRoute($route, array $parameters = [], $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route         The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * @param string $date
     * @return \MongoDate
     */
    public function processMongoDate($date)
    {
        if (!empty($date)) {
            $phpDate = date_create_from_format('Y-m-d\TH:i:s.uP', $date);
            return new \MongoDate($phpDate->getTimestamp(), $phpDate->format('u'));
        } else {
            return null;
        }
    }

    /**
     * @param null|string $startDate
     * @return string
     */
    public function processSqlStartDateTime($startDate)
    {
        if (!empty($startDate)) {
            $dateTime = strtotime($startDate);
            return date("Y-m-d H:i:s", $dateTime);
        } else {
            return null;
        }
    }

    /**
     * @param null|string $endDate
     * @return string
     */
    public function processSqlEndDateTime($endDate)
    {
        if (!empty($endDate)) {
            $dateTime = strtotime($endDate." 23:59:59");
            return date("Y-m-d H:i:s", $dateTime);
        } else {
            return null;
        }
    }


    /**
     * @param SlidingPagination $phrasesPagination
     * @return int
     */
    public function getLastItemPagination($phrasesPagination)
    {
        $totalItemsFoundPagination = $phrasesPagination->getTotalItemCount();


        if($totalItemsFoundPagination > 0) {

            $limitItensPerPage = $phrasesPagination->getItemNumberPerPage();

            /*
             * Define o número máximo de items na paginação
             */
            return ceil($totalItemsFoundPagination / $limitItensPerPage);
        }

        return 1;
    }
}