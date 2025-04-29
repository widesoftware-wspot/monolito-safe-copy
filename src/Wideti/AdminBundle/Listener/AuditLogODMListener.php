<?php
namespace Wideti\AdminBundle\Listener;

use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;


use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;


class AuditLogODMListener
{
    use SessionAware;
    use SecurityAware;

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
        $em = $args->getDocumentManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledDocumentInsertions() as $entity) {

            $isAuditable = $this->auditLogService->getKindAndIdentifierIfAuditable($entity);
            if ($isAuditable) {
                $this->auditLogService->createAuditLog(
                    $entity,
                    Events::create()->getValue(),
                    null,
                    true
                );
            }
        }

        foreach ($uow->getScheduledDocumentUpdates() as $entity) {

            $isAuditable = $this->auditLogService->getKindAndIdentifierIfAuditable($entity);

            if ($isAuditable) {
                
                $changes = $this->getChanges($uow, $entity, $em);

                if ($changes) {
                    $this->auditLogService->createAuditLog(
                        $entity,
                        Events::update()->getValue(),
                        $changes,
                        true
                    );
                }
            }
        }

        foreach ($uow->getScheduledDocumentDeletions() as $entity) {

            $isAuditable = $this->auditLogService->getKindAndIdentifierIfAuditable($entity);
            if ($isAuditable) {
                $this->auditLogService->createAuditLog(
                    $entity, 
                    Events::delete()->getValue(),
                    null,
                    true
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
    private function getChanges($uow, $entity, $em)
    {
        $changeset = $uow->getDocumentChangeSet($entity);
        return $this->auditLogService->getAuditableChanges(
            $entity,
            $changeset,
            $em
        );
    }
}
