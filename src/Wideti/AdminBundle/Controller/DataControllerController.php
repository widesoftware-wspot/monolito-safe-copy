<?php

namespace Wideti\AdminBundle\Controller;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\DataProtect\DataController\DataControllerServiceInterface;
use Wideti\DomainBundle\Service\DataProtect\DataController\Dto\DataControllerAgentDto;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\DataControllerNotFoundRuntimeException;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\FieldRequiredRuntimeException;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\InvalidFormatRuntimeException;
use Wideti\DomainBundle\Service\DataProtect\DataController\RequestValidation\DataControllerRequestValidation;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class DataControllerController
{
    use TwigAware;
    use SessionAware;

    /**
     * @var DataControllerServiceInterface
     */
    private $dataControllerService;

    /**
     * DataControllerController constructor.
     * @param DataControllerServiceInterface $dataControllerService
     */
    public function __construct(DataControllerServiceInterface $dataControllerService)
    {
        $this->dataControllerService = $dataControllerService;
    }

    public function indexAction()
    {
        return $this->render('AdminBundle:DataController:index.html.twig');
    }

    public function createAction(Request $request)
    {
        try {
            DataControllerRequestValidation::validation($request);
            $dataControllerAgentDto = DataControllerAgentDto::createByRequest($request);
            /**
             * @var $client Client
             */
            $client     = $this->getLoggedClient();
            $this->dataControllerService->save($dataControllerAgentDto, $client);
            return JsonResponse::create($dataControllerAgentDto->toArrayMap(), 200);
        }catch (FieldRequiredRuntimeException  $e){
            return $this->returnFieldRequired($e);
        }catch (InvalidFormatRuntimeException $e){
            return $this->returnInvalidDateFormat($e);
        }
    }

    private function returnInvalidDateFormat(InvalidFormatRuntimeException $e)
    {
        return JsonResponse::create(
            [
                'message' => $e->getMessage(),
                'type' => "INVALID_FIELD",
                'field' => $e->getField()
            ],
            400
        );
    }

    private function returnFieldRequired(FieldRequiredRuntimeException $e)
    {
        return JsonResponse::create(
            [
                'message' => $e->getMessage(),
                'type' => "FIELD_REQUIRED",
                'field' => $e->getFieldRequired()
            ],
            400
        );
    }

    public function updateAction(Request $request)
    {
        try {
            DataControllerRequestValidation::validation($request);
            $dataControllerAgentDto = DataControllerAgentDto::createByRequest($request);
            /**
             * @var $client Client
             */
            $client     = $this->getLoggedClient();
            $this->dataControllerService->update($dataControllerAgentDto, $client);
            return JsonResponse::create($dataControllerAgentDto->toArrayMap(), 200);
        }catch (FieldRequiredRuntimeException  $e){
            return $this->returnFieldRequired($e);
        }catch (InvalidFormatRuntimeException $e){
            return $this->returnInvalidDateFormat($e);
        }
    }

    public function findAction()
    {
        try {
            /**
             * @var $client Client
             */
            $client     = $this->getLoggedClient();
            $dataControllerAgentDto = $this->dataControllerService->getDataControllerAgent($client);
            return JsonResponse::create($dataControllerAgentDto->toArrayMap(), 200);
        }catch (DataControllerNotFoundRuntimeException $e){
            return JsonResponse::create(
                [
                    'message' => $e->getMessage()
                ],
                404
            );
        }
    }
}