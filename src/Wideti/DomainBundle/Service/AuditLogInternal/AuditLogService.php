<?php

namespace Wideti\DomainBundle\Service\AuditLogInternal;

use DateTime;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Document\Group\ConfigurationValue;
use Wideti\DomainBundle\Document\Group\Configuration;

use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\ApiWSpotRoles;
use Wideti\DomainBundle\Entity\ApiWSpotResources;
use Wideti\DomainBundle\Entity\AuditLog;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientConfiguration;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Blacklist;
use Wideti\DomainBundle\Entity\BusinessHours;
use Wideti\DomainBundle\Entity\BusinessHoursItem;
use Wideti\DomainBundle\Entity\DataControllerAgent;
use Wideti\DomainBundle\Entity\ClientsLegalBase;
use Wideti\DomainBundle\Entity\ApiRDStation;
use Wideti\DomainBundle\Entity\ApiEgoi;
use Wideti\DomainBundle\Entity\OAuthLogin;
use Wideti\DomainBundle\Entity\ModuleConfigurationValue;
use Wideti\DomainBundle\Entity\CustomFieldTemplate;

use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

/**
 * Class AuditLogService
 * @package Wideti\DomainBundle\Service\AuditLog
 */
class AuditLogService
{
    use EntityManagerAware;
    use SessionAware;
    use SecurityAware;
    use LoggerAware;

    private $fksMysqlToMongo = [
        'Ixc_client_group' => [
            'repository' => 'DomainBundle:Group\Group',
            'fieldRelated' => '_id',
            'methodGetIdentifier' => "getName"
        ],
        'hubsoft_client_group' => [
            'repository' => 'DomainBundle:Group\Group',
            'fieldRelated' => '_id',
            'methodGetIdentifier' => "getName"
        ],
        'guest/group' => [
            'repository' => 'DomainBundle:Group\Group',
            'fieldRelated' => 'shortcode',
            'methodGetIdentifier' => "getName"
        ],
        'guest_sso_manager/customizeGuestGroup'=> [
            'repository' => 'DomainBundle:Group\Group',
            'fieldRelated' => '_id',
            'methodGetIdentifier' => "getName"
        ],
    ];

    private $nonPersistedKindMap = [
        'consent'                       => 'consent',
        'export-guest'                  => 'export',
        'export-historic'               => 'export',
        'export-online-guests'          => 'export',
        'export-download-upload'        => 'export',
        'export-download-upload-detail' => 'export',
        'export-records-per-day'        => 'export',
        'export-most-visited-hours'     => 'export',
        'export-access-points'          => 'export',
        'export-campaign'               => 'export',
        'export-call-to-action'         => 'export',
        'export-sms-report'             => 'export',
        'export-guests-report'          => 'export',
        'sms-marketing'                 => 'sms-marketing',
    ];

    /**
     * Cria um log de auditoria baseado nos dados fornecidos.
     */
    public function createAuditLog($entity, $eventType, $changes = null, $forceFlush = false, $ownerCollection = null)
    {
        if (!$client = $this->getLoggedClient()) {
            return;
        }

        try {
            $entityAuditData = $this->getKindAndIdentifierIfAuditable($entity, $ownerCollection);
        }  catch (\Exception $e) {
            $this->logger->addCritical("Error to createAuditLog: {$e->getMessage()}");
        }

        if (array_key_exists('actions', $entityAuditData) && !in_array($eventType, $entityAuditData['actions'], true)) {
            return;
        }
        if (!$entityAuditData) {
            return;
        }

        $auditLog = new AuditLog();
        $auditLog->setClientId($client->getId());
        $user = $this->getUser();
        if (!$user && $entityAuditData['kind'] == Kinds::userAdmin()->getValue()) {
            $auditLog->setSourceUsername("system");
        } else if ($user && get_class($user) == ApiWspot::class) {
            $auditLog->setSourceId($user->getId());
            $auditLog->setSourceUsername("API - " . $user->getName());
        } else {
            $auditLog->setSourceId($user->getId());
            $auditLog->setSourceUsername($user->getUsername()); 
        }
        $auditLog->setEventType($eventType);
        $auditLog->setTargetKind($entityAuditData['kind']);
        $auditLog->setTargetIdentifier($entityAuditData['identifier']);
        $auditLog->setCreatedAt(new DateTime("now", new \DateTimeZone("America/Sao_Paulo")));

        if ($user && get_class($user) == ApiWspot::class && get_class($entity) == Client::class && $entity->getId()) {
            $auditLog->setClientId($entity->getId());
        }
        $targetId = $entity && method_exists($entity, $entityAuditData['targetIdMethod']) ? call_user_func([$entity, $entityAuditData['targetIdMethod']]) : null;
        $auditLog->setTargetId($targetId);

        if ($changes) {
            $auditLog->setChanges($changes);
        }

        $this->em->persist($auditLog);
        if ($forceFlush) {
            $this->em->flush();
        }
    }

    public function getAuditableChanges($entity, $changeSet, $mongo = null)
    {
        $entityAuditData = $this->getKindAndIdentifierIfAuditable($entity);

        $changes = [];
        foreach ($changeSet as $field => $values) { 
            $oldValue = $values[0];
            $newValue = $values[1];
            if (in_array($field, $entityAuditData['ignoreFields'], true)) {
                continue;
            }

            if ($this->areValuesEquivalent($oldValue, $newValue)) {
                continue;
            }

            $oldValue = $this->serializeEntityField($oldValue);
            $newValue = $this->serializeEntityField($newValue);
            if (array_key_exists('type', $entityAuditData) && $entityAuditData['type'] == "checkbox") {
                $oldValue = (bool)$oldValue;
                $newValue = (bool)$newValue;
            }
            if (array_key_exists('type', $entityAuditData) && $entityAuditData['type'] == "sensitive") {
                $oldValue = str_repeat('*', strlen($oldValue));
                $newValue = str_repeat('*', strlen($newValue));
            }
            if ((stripos($field, "password") !== false) || (stripos($field, "secret") !== false) || (stripos($field, "token") !== false)) {
                $oldValue = str_repeat('*', strlen($oldValue));
                $newValue = str_repeat('*', strlen($newValue));
            }
            if (array_key_exists('key', $entityAuditData) && array_key_exists($entityAuditData['key'], $this->fksMysqlToMongo)) {
                $fksInfo = $this->fksMysqlToMongo[$entityAuditData['key']];
                $oldField = $mongo->getRepository($fksInfo['repository'])
                    ->findOneBy([$fksInfo['fieldRelated'] => $oldValue ]);
                $newField = $mongo->getRepository($fksInfo['repository'])
                    ->findOneBy([$fksInfo['fieldRelated'] => $newValue ]);
                if ($oldField) {
                    $oldValue = call_user_func([$oldField, $fksInfo['methodGetIdentifier']]);
                }
                if ($newField) {
                    $newValue = call_user_func([$newField, $fksInfo['methodGetIdentifier']]);
                }
            }
            if (array_key_exists($entityAuditData['kind'] . "/" . $field, $this->fksMysqlToMongo)) {
                $fksInfo = $this->fksMysqlToMongo[$entityAuditData['kind'] . "/" . $field];
                $oldField = $mongo->getRepository($fksInfo['repository'])
                    ->findOneBy([$fksInfo['fieldRelated'] => $oldValue]);
                $newField = $mongo->getRepository($fksInfo['repository'])
                    ->findOneBy([$fksInfo['fieldRelated'] => $newValue]);
                if ($oldField) {
                    $oldValue = call_user_func([$oldField, $fksInfo['methodGetIdentifier']]);
                }
                if ($newField) {
                    $newValue = call_user_func([$newField, $fksInfo['methodGetIdentifier']]);
                }
            }
            if ($oldValue instanceof DateTime && $newValue instanceof DateTime) {
                $changes[$field] = [
                    'old' => $oldValue->format('Y-m-d H:i:s'),
                    'new' => $newValue->format('Y-m-d H:i:s'),
                ];
            } else if (is_array($newValue) && is_array($oldValue)) {
                foreach ($newValue as $key => $new) {
                    if (array_key_exists($key, $oldValue)) {
                        if ($new !== $oldValue[$key]) {
                            $changes[$field][$key]["old"] = $oldValue[$key];
                            $changes[$field][$key]["new"] = $new;
                        }
                    } else {
                        $changes[$field] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }
            } else {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return !empty($changes) ? $changes : null;
    }

    /**
     * Verifica se dois valores são equivalentes e podem ser ignorados.
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return bool
     */
    private function areValuesEquivalent($oldValue, $newValue)
    {
        if (empty($oldValue) && empty($newValue)) {
            return true;
        }

        if ($oldValue == $newValue) {
            return true;
        }

        return (is_bool($oldValue) || is_bool($newValue)) && (bool)$oldValue === (bool)$newValue;
    }

    private function serializeEntityField($entityField)
    {
        if (method_exists($entityField, '__toString')) {
            return (string) $entityField;
        }

        return $entityField;
    }

    /**
     * Retorna o Kind e identificador correspondente à entidade.
     */
    public function getKindAndIdentifierIfAuditable($entity, $ownerCollection = null)
    {
        if (is_string($entity)) {
            if (array_key_exists($entity, $this->nonPersistedKindMap)) {
                return [
                    'kind' => $this->nonPersistedKindMap[$entity],
                    'identifier' => $entity,
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId',
                ];
            }
            return null;
        }

        $entityClass = ($ownerCollection) ? get_class($ownerCollection) : get_class($entity);
        
        switch ($entityClass) {
            case Guest::class:
                return [
                    'kind' => Kinds::guest()->getValue(),
                    'identifier' => $entity->getPropertyByKey($entity->getLoginField()),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case Group::class:
                return [
                    'kind' => Kinds::group()->getValue(),
                    'identifier' => $entity->getName(),
                    'ignoreFields' => ["configurations"],
                    'targetIdMethod' => 'getId'
                ];
            case Configuration::class:
                return [
                    'kind' => Kinds::group()->getValue(),
                    'identifier' => $entity->getShortcode(),
                    'ignoreFields' => ["configurationValues"],
                    'actions' => ["update"],
                    'targetIdMethod' => 'getId'
                ];
            case ConfigurationValue::class:
                return [
                    'kind' => Kinds::group()->getValue(),
                    'identifier' => $entity->getKey(),
                    'actions' => ["update"],
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case Field::class:
                return [
                    'kind' => "form_fields",
                    'identifier' => $entity->getName()["pt_br"],
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getIdentifier'
                ];
            case CustomFieldTemplate::class:
                return [
                    'kind' => "custom_form_fields",
                    'identifier' => $entity->getName()["pt_br"],
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getIdentifier'
                ];
            case Users::class:
                return [
                    'kind' => Kinds::userAdmin()->getValue(),
                    'identifier' => $entity->getUsername(),
                    'ignoreFields' => ['salt', 'deletedAt'],
                    'targetIdMethod' => 'getId'
                ];
            case Client::class:
                return [
                    'kind' => $ownerCollection ? $this->em->getClassMetadata(get_class($entity))->getTableName() : Kinds::client()->getValue(),
                    'identifier' => $ownerCollection ? $ownerCollection->getDomain() . " - " . $entity->getShortCode() : $entity->getDomain(),
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case AccessPoints::class:
                return [
                    'kind' => Kinds::accessPoint()->getValue(),
                    'identifier' => $entity->getFriendlyName(),
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case AccessPointsGroups::class:
                return [
                    'kind' => $ownerCollection ? $this->em->getClassMetadata($entityClass)->getTableName() : Kinds::accessPointGroup()->getValue(),
                    'identifier' => $ownerCollection ? $entity->getFriendlyName() : $entity->getGroupName(),
                    'ignoreFields' => ['updated'],
                    'actions' => ['create', 'update'],
                    'targetIdMethod' => 'getId'
                ];
            case Template::class:
                return [
                    'kind' => Kinds::template()->getValue(),
                    'identifier' => $entity->getName(),
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case Campaign::class:
                return [
                    'kind' => Kinds::campaign()->getValue(),
                    'identifier' => $entity->getId(),
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case AccessCode::class:
                return [
                    'kind' => Kinds::accessCode()->getValue(),
                    'identifier' => $entity->getId(),
                    'targetIdMethod' => 'getId',
                    'ignoreFields' => ['lotNumber', 'updated'],
                ];
            case Blacklist::class:
                return [
                    'kind' => Kinds::deviceLock()->getValue(),
                    'identifier' => $entity->getMacAddress(),
                    'ignoreFields' => ['created'],
                    'targetIdMethod' => 'getId'
                ];
            case BusinessHours::class:
                return [
                    'kind' => Kinds::businessHours()->getValue(),
                    'identifier' => $entity->getId(),
                    'actions' => ['create', 'update', 'delete'],
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case BusinessHoursItem::class:
                return [
                    'kind' => Kinds::businessHours()->getValue(),
                    'identifier' => $entity->getBusinessHours()->getId(),
                    'ignoreFields' => [],
                    'actions' => ['update'],
                    'targetIdMethod' => 'getId'
                ];
            case Segmentation::class:
                return [
                    'kind' => Kinds::segmentation()->getValue(),
                    'identifier' => $entity->getTitle(),
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case DataControllerAgent::class:
                return [
                    'kind' => Kinds::dataController()->getValue(),
                    'identifier' => $entity->getEmail(),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case ClientsLegalBase::class:
                return [
                    'kind' => Kinds::system()->getValue(),
                    'identifier' => $entity->getLegalKind()->getKind(),
                    'ignoreFields' => ['timestamp'],
                    'targetIdMethod' => 'getId'
                ];
            case ApiWspot::class:
                return [
                    'kind' => "api_wspot",
                    'identifier' => "",
                    'targetIdMethod' => 'getId',
                    'ignoreFields' => ["updated"],
                ];
            case ApiWspot::class:
                return [
                    'kind' => "api_wspot",
                    'identifier' => $entity->getName(),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case ApiWspotRoles::class:
                return [
                    'kind' => "api_wspot_roles",
                    'identifier' => $entity->getApi()->getName() . " - " . $entity->getRole(),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case ApiWspotResources::class:
                return [
                    'kind' => "api_wspot_resources",
                    'identifier' => $entity->getApi()->getName() . " - " . $entity->getResource() . " - " . $entity->getMethod(),
                    'ignoreFields' => ["updated"],
                    'targetIdMethod' => 'getId'
                ];
            case ApiRDStation::class:
                return [
                    'kind' => "api_rd_station",
                    'identifier' => $ownerCollection ? $ownerCollection->getTitle() . " - " . $entity->getIdentifier() : $entity->getTitle(),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case ApiEgoi::class:
                return [
                    'kind' =>  $ownerCollection ? "api_egoi[" . $this->em->getClassMetadata(get_class($entity))->getTableName() . "]" : "api_egoi",
                    'identifier' => $ownerCollection ? $ownerCollection->getTitle() . " - " . $entity->getIdentifier() : $entity->getTitle(),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case OAuthLogin::class:
                return [
                    'kind' =>  "guest_sso_manager",
                    'identifier' => $entity->getName(),
                    'ignoreFields' => [],
                    'targetIdMethod' => 'getId'
                ];
            case ModuleConfigurationValue::class:
                $type = "";
                $actions = ["update", "delete"];
                if ($entity->getItems()) {
                    $type = $entity->getItems()->getType();
                    $key = $entity->getItems()->getKey();
                    if (stripos($key, "password") || stripos($key, "secret") || stripos($key, "token")) {
                        $type = "sensitive";
                    }
                    if (stripos($key, "enable")) {
                        $actions = ["update", "delete"];
                    }
                }
                return [
                    'kind' => $entity->getItems() ? $entity->getItems()->getModule()->getShortCode() : $entity->getId(),
                    'identifier' => $entity->getItems() ? $entity->getItems()->getLabel() : $entity->getId(),
                    'key' => $entity->getItems() ? $entity->getItems()->getKey() : $entity->getId(),
                    'targetIdMethod' => 'getId',
                    'type' => $type,
                    'ignoreFields' => [],
                    'actions' => $actions
                ];
            case WhiteLabel::class:
                return [
                    'kind' => Kinds::whitelabel()->getValue(),
                    'identifier' => $entity->getCompanyName(),
                    'ignoreFields' => ['updated'],
                    'targetIdMethod' => 'getId'
                ];
            case ClientConfiguration::class:
                return [
                    'kind' => Kinds::clientConfiguration()->getValue(),
                    'identifier' => $entity->getConfiguration()->getLabel(),
                    'ignoreFields' => [],
                    'actions' => ['update'],
                    'targetIdMethod' => 'getId'
                ];
            default:
                return null;
        }
    }
}
