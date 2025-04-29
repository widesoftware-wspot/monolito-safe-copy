<?php
namespace Wideti\AdminBundle\Listener;

use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

use Doctrine\ORM\Event\OnFlushEventArgs;


use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;


class AuditLogListener
{
    use SessionAware;
    use SecurityAware;
    use MongoAware;

    private $auditLogService;
    private $requestStack;

    public function __construct(AuditLogService $auditLogService, $requestStack)
    {
        $this->auditLogService = $auditLogService;
        $this->requestStack = $requestStack;
    }

    /**
     * @param $uri
     * @return bool
     */
    private function isAuditableUrl($request)
    {
        if ($request) {
            $uri = $request->getPathInfo();
            return strpos($uri, '/admin') !== false || strpos($uri, '/api') !== false;
        }

        return false;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->isAuditableUrl($this->requestStack->getCurrentRequest())) {
            return ;
        }
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // Iteração sobre as entidades que estão sendo inseridas
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $isAuditable = $this->auditLogService->getKindAndIdentifierIfAuditable($entity);
            if ($isAuditable) {
                $this->auditLogService->createAuditLog(
                    $entity, 
                    Events::create()->getValue()
                );
            }
        }

        // Iteração sobre as entidades que estão sendo atualizadas
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $isAuditable = $this->auditLogService->getKindAndIdentifierIfAuditable($entity);
            if ($isAuditable) {
                $changes = $this->getChanges($uow, $entity);
                if ($changes) {
                    $this->auditLogService->createAuditLog(
                        $entity, 
                        Events::update()->getValue(), 
                        $changes
                    );
                }
            }
        }

        // Iteração sobre as entidades que estão sendo deletadas
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $isAuditable = $this->auditLogService->getKindAndIdentifierIfAuditable($entity);
            if ($isAuditable) {
                $this->auditLogService->createAuditLog(
                    $entity, 
                    Events::delete()->getValue()
                );
            }
        }

        foreach ($uow->getScheduledCollectionUpdates() as $collectionUpdate) {
            $owner = $collectionUpdate->getOwner();
            $added = $collectionUpdate->getInsertDiff();
            foreach ($added as $childEntity) {
                $this->auditLogService->createAuditLog(
                    $childEntity, 
                    Events::create()->getValue(),
                    false,
                    false,
                    $owner
                );
            }

            $removed = $collectionUpdate->getDeleteDiff(); 
            foreach ($removed as $childEntity) {
                $this->auditLogService->createAuditLog(
                    $childEntity, 
                    Events::delete()->getValue(),
                    false,
                    false,
                    $owner
                );
            }
        }
    }

    /**
     * Retorna o changeset de uma entidade. // (passar pro service)
     *
     * @param \Doctrine\ORM\UnitOfWork $uow
     * @param object $entity
     * @return array|null
     */
    private function getChanges($uow, $entity)
    {
        $changeset = $uow->getEntityChangeSet($entity);
        return $this->auditLogService->getAuditableChanges(
            $entity,
            $changeset,
            $this->mongo
        );
    }
}
