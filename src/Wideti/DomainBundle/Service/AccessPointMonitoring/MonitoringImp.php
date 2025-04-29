<?php

namespace Wideti\DomainBundle\Service\AccessPointMonitoring;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\AccessPointMonitoring;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointMonitoringRepository;
use Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto\FolderDto;
use Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Dto\ResponseDto;
use Wideti\DomainBundle\Service\AccessPointMonitoring\ViewerPlatform\Grafana\Grafana;

class MonitoringImp implements Monitoring
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Grafana
     */
    private $viewerPlatform;
    /**
     * @var AccessPointMonitoringRepository
     */
    private $repository;

    /**
     * MonitoringImp constructor.
     * @param EntityManager $em
     * @param Grafana $viewerPlatform
     * @param AccessPointMonitoringRepository $repository
     */
    public function __construct(
        EntityManager $em,
        Grafana $viewerPlatform,
        AccessPointMonitoringRepository $repository
    ) {
        $this->em = $em;
        $this->viewerPlatform = $viewerPlatform;
        $this->repository = $repository;
    }

    public function getDashboard(AccessPoints $accessPoint)
    {
        $dashboard = $this->repository->findOneBy([
            'accessPoint' => $accessPoint
        ]);

        if (!$dashboard) {
            return $this->createDashboard($accessPoint);
        }

        return $dashboard;
    }

    private function createDashboard(AccessPoints $accessPoint)
    {
        $schema = $this->getAndTransformOriginalSchemaWithClientParams($accessPoint);

        $dashboard = new AccessPointMonitoring($schema, $accessPoint);
        $this->persist($dashboard);

        $this->viewerPlatform->createDashboard($schema);

        return $dashboard;
    }

    private function getAndTransformOriginalSchemaWithClientParams(AccessPoints $accessPoint)
    {
        $client     = $accessPoint->getClient();
        $folderId   = $this->getFolderId($client);

        $schemaJson = file_get_contents(__DIR__ . "/schema.json");
        $schemaJson = str_replace("dominio_empresa", $client->getDomain(), $schemaJson);
        $schemaJson = str_replace("identificador_ponto_acesso", $accessPoint->getIdentifier(), $schemaJson);

        $schemaArray = json_decode($schemaJson, true);
        $schemaArray['dashboard']['uid']    = (string)$accessPoint->getId();
        $schemaArray['dashboard']['title']  = $accessPoint->getIdentifier();
        $schemaArray['folderId']            = $folderId;

        return json_encode($schemaArray);
    }

    private function getFolderId(Client $client)
    {
        /**
         * @var ResponseDto $response
         */
        $response = $this->viewerPlatform->getFolderByName($client->getDomain());

        if ($response->statusCode != 200) {
            $folderDto = new FolderDto($client->getDomain(), $client->getDomain());
            $response = $this->viewerPlatform->createFolder($folderDto);
        }

        return $response->getMessage()['id'];
    }

    public function removeDashboard($uid)
    {
        $this->viewerPlatform->removeDashboard($uid);

        $dashboard = $this->repository->findOneBy([
            'accessPoint' => $uid
        ]);

        if ($dashboard) {
            $this->remove($dashboard);
        }
    }

    private function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    private function remove($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }
}
