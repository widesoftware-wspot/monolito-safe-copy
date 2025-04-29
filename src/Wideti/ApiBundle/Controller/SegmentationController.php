<?php

namespace Wideti\ApiBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\SegmentationHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\ApiEntityValidator\ApiValidator;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Client\SelectClientByRequestService;
use Wideti\DomainBundle\Service\Segmentation\CreateSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\DeleteSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\Dto\ExportDto;
use Wideti\DomainBundle\Service\Segmentation\Dto\SegmentationDto;
use Wideti\DomainBundle\Service\Segmentation\EditSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\ExportSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;
use Wideti\DomainBundle\Service\Segmentation\GetDefaultSchemaService;
use Wideti\DomainBundle\Service\Segmentation\ListSegmentationService;
use Wideti\DomainBundle\Service\Segmentation\PreviewSegmentationService;

/**
 * Class SegmentationController
 * @package Wideti\ApiBundle\Controller
 */
class SegmentationController implements ApiResource
{
    /**
     * @var SelectClientByRequestService
     */
    private $selectClientByRequest;
    /**
     * @var ApiValidator
     */
    private $apiValidator;
    /**
     * @var PreviewSegmentationService
     */
    private $previewSegmentationService;
    /**
     * @var CreateSegmentationService
     */
    private $createSegmentationService;
    /**
     * @var EditSegmentationService
     */
    private $editSegmentationService;
    /**
     * @var ListSegmentationService
     */
    private $listSegmentationService;
    /**
     * @var DeleteSegmentationService
     */
    private $deleteSegmentationService;
    /**
     * @var GetDefaultSchemaService
     */
    private $defaultSchemaService;
    /**
     * @var ExportSegmentationService
     */
    private $exportSegmentationService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * SegmentationController constructor.
     * @param SelectClientByRequestService $selectClientByRequest
     * @param ApiValidator $apiValidator
     * @param PreviewSegmentationService $previewSegmentationService
     * @param CreateSegmentationService $createSegmentationService
     * @param EditSegmentationService $editSegmentationService
     * @param ListSegmentationService $listSegmentationService
     * @param DeleteSegmentationService $deleteSegmentationService
     * @param GetDefaultSchemaService $defaultSchemaService
     * @param ExportSegmentationService $exportSegmentationService
     * @param Logger $logger
     * @param ClientService $clientService
     * @param Auditor $auditor
     */
    public function __construct(
        SelectClientByRequestService $selectClientByRequest,
        ApiValidator $apiValidator,
        PreviewSegmentationService $previewSegmentationService,
        CreateSegmentationService $createSegmentationService,
        EditSegmentationService $editSegmentationService,
        ListSegmentationService $listSegmentationService,
        DeleteSegmentationService $deleteSegmentationService,
        GetDefaultSchemaService $defaultSchemaService,
        ExportSegmentationService $exportSegmentationService,
        Logger $logger,
        ClientService $clientService,
        Auditor $auditor
    ) {
        $this->selectClientByRequest = $selectClientByRequest;
        $this->apiValidator = $apiValidator;
        $this->previewSegmentationService = $previewSegmentationService;
        $this->createSegmentationService = $createSegmentationService;
        $this->editSegmentationService = $editSegmentationService;
        $this->listSegmentationService = $listSegmentationService;
        $this->deleteSegmentationService = $deleteSegmentationService;
        $this->defaultSchemaService = $defaultSchemaService;
        $this->exportSegmentationService = $exportSegmentationService;
        $this->logger = $logger;
        $this->clientService = $clientService;
        $this->auditor = $auditor;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function previewAction(Request $request)
    {
        $required = ['type', 'items'];
        $errorSchema = $this->apiValidator->requiredFields($request, $required);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, Response::HTTP_BAD_REQUEST);
        }

        $filter = FilterDto::createFromRequest($request);

        try {
            $result = $this->previewSegmentationService->preview($filter);

            //Auditoria
            $client = $this->getClient($request);
            foreach ($result['preview'] as $r) {
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $filter->getUserId())
                    ->onTarget(Kinds::guest(), $r['id'])
                    ->withType(Events::view())
                    ->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante na tela de cadastro de segmentação')
                    ->addDescription(AuditEvent::EN_US, 'User viewed visitor on the segmentation registration screen')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario en la pantalla de registro de segmentación');
                $this->auditor->push($event);
            }

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation preview error.', [
                'error' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $required = ['title', 'filter'];
        $errorSchema = $this->apiValidator->requiredFields($request, $required);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, Response::HTTP_BAD_REQUEST);
        }

        $segmentation = SegmentationDto::createFromRequest($request);

        try {
            $result = $this->createSegmentationService->create($segmentation);
            return new JsonResponse(SegmentationDto::convertEntityToDto($result), Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation create error.', [
                'error' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function defaultSchemaAction()
    {
        $schema = $this->defaultSchemaService->get();
        return new JsonResponse($schema, Response::HTTP_OK);
    }

    public function editAction(Request $request)
    {
        $required = ['title', 'status', 'filter'];
        $segmentationId = $request->get('id', null);

        if (!$segmentationId) {
            return new JsonResponse([
                "error" => "ID da segmentação não informado"
            ], Response::HTTP_BAD_REQUEST);
        }

        $errorSchema = $this->apiValidator->requiredFields($request, $required);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, Response::HTTP_BAD_REQUEST);
        }

        $segmentation = SegmentationDto::createFromRequest($request);

        try {
            $result = $this->editSegmentationService->edit($segmentationId, $segmentation);
            return new JsonResponse(SegmentationDto::convertEntityToDto($result), Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation edit error.', [
                'error' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        try {
            $client = $this->selectClientByRequest->get($request);
            $segmentations = $this->listSegmentationService->listAll($client);
            $apiSegmentations = SegmentationDto::createFromArray($segmentations);

            $httpStatus = empty($apiSegmentations)
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_OK;

            return new JsonResponse($apiSegmentations, $httpStatus);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation list error.', [
                'error' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                "error" => 'Ocorreu um erro, nossa equipe já foi notificada.'
            ], 500);
        }
    }

    public function deleteAction(Request $request)
    {
        $segmentationId = $request->get('id', null);

        if (!$segmentationId) {
            return new JsonResponse([
                "error" => "ID da segmentação não informado."
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->deleteSegmentationService->delete($segmentationId);
            return new JsonResponse([
                "msg" => "Registro removido com sucesso."
            ], Response::HTTP_OK);
        } catch (DocumentNotFoundException $e) {
            return new JsonResponse([
                "error" => "Registro não encontrado."
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation remove error.', [
                'error' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function convertToSchemaAction(Request $request)
    {
        $defaultSchema = $this->defaultSchemaService->get();
        $filter = json_decode($request->getContent(), true);

        try {
            $result = SegmentationHelper::convertToSchema($filter, $defaultSchema);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation convert filter error.', [
                'error' => $e->getMessage()
            ]);
            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    public function exportAction(Request $request)
    {
        $required = ['client', 'segmentationId', 'recipient'];
        $errorSchema = $this->apiValidator->requiredFields($request, $required);

        if (count($errorSchema) > 0) {
            return new JsonResponse($errorSchema, Response::HTTP_BAD_REQUEST);
        }

        $exportDto = ExportDto::createFromRequest($request);

        try {
            $this->exportSegmentationService->requestingExport($exportDto);
            return new JsonResponse([
                "msg" => "A exportação está sendo processada. Em breve o arquivo será enviado para seu e-mail."
            ], Response::HTTP_OK);
        } catch (DocumentNotFoundException $e) {
            return new JsonResponse([
                "error" => "Segmentação não encontrada."
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->addCritical('[API ERROR] Segmentation export error.', [
                'error' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                "error" => "Ocorreu um erro, nossa equipe já foi notificada."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getResourceName()
    {
        return "segmentation";
    }

    /**
     * @param Request $request
     * @return Client
     */
    private function getClient(Request $request)
    {
        $domain = StringHelper::getClientDomainByUrl($request->getHttpHost());
        return $this->clientService->getClientByDomain($domain);
    }
}
