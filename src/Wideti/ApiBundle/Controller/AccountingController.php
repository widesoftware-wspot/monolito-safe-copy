<?php

namespace Wideti\ApiBundle\Controller;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Gedmo\Exception\InvalidArgumentException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Exception\DateInvalidException;
use Wideti\DomainBundle\Exception\InvalidGuestIdException;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\AccountingStreamRepository;
use Wideti\DomainBundle\Service\Client\SelectClientByRequestService;
use Wideti\DomainBundle\Service\Client\SelectClientByRequestServiceImp;
use Wideti\DomainBundle\Service\Radacct\AccountingStreamServiceImp;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;
use Wideti\DomainBundle\Service\Radacct\GetAccountingData;
use Wideti\DomainBundle\Service\Radacct\GetAccountingDataImp;

/**
 * Class AccountingController
 * @package Wideti\ApiBundle\Controller
 */
class AccountingController implements ApiResource
{
    /**
     * @var SelectClientByRequestService
     */
    private $selectClientByRequest;
    /**
     * @var AccountingStreamServiceImp
     */
    private $accountingStreamService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var GetAccountingData
     */
    private $getAccountingData;

    /**
     * AccountingController constructor.
     * @param SelectClientByRequestService $selectClientByRequest
     * @param AccountingStreamServiceImp $accountingStreamService
     * @param Logger $logger
     */
    public function __construct(
        SelectClientByRequestService $selectClientByRequest,
        AccountingStreamServiceImp $accountingStreamService,
        Logger $logger,
        GetAccountingData $getAccountingData
    )
    {
        $this->selectClientByRequest   = $selectClientByRequest;
        $this->accountingStreamService = $accountingStreamService;
        $this->logger                  = $logger;
        $this->getAccountingData       = $getAccountingData;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getStreamAction(Request $request)
    {
        try {
            $client = $this->selectClientByRequest->get($request);
            $filter = AcctStreamFilterDto::createFromRequest($request, $client);
            $result = $this->accountingStreamService->get($filter);
            $statusCode = $result->getTotalRegistries() > 0 ? Response::HTTP_OK : Response::HTTP_NOT_FOUND;
            return new JsonResponse($result, $statusCode);
        } catch (BadRequest400Exception $e) {
            return new JsonResponse(["error" => "nextToken inválido"], Response::HTTP_BAD_REQUEST);
        } catch (Missing404Exception $e) {
            return new JsonResponse(
                ["error" => "Você ultrapassou o limite de 60 segundos do nextToken"],
                Response::HTTP_BAD_REQUEST);
        } catch (DateInvalidException $e) {
            return new JsonResponse(["error" => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (InvalidGuestIdException $e) {
            return new JsonResponse(["error" => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->addCritical("[API][ACCOUNTING] {$e->getMessage()}", $e->getTrace());

            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAccountingDataAction(Request $request)
    {
        return new JsonResponse($this->getAccountingData->get($request));
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return "accounting";
    }
}