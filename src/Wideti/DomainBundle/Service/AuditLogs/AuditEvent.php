<?php


namespace Wideti\DomainBundle\Service\AuditLogs;


use phpDocumentor\Reflection\Types\Array_;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventCreate;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventType;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kind;

class AuditEvent
{
    const PT_BR = "pt_BR";
    const EN_US = "en_US";
    const ES_ES = "es_ES";

    /**
     * @var $clientId int
     * @description the client id
     */
    private $clientId;
    /**
     * @var $timestamp int
     * @description the UTC timestamp in Milliseconds
     */
    private $timestamp;
    /**
     * @var $sourceKind Kind
     * @description The entity name from who execute the action
     */
    private $sourceKind;
    /**
     * @var $sourceId string
     * @description The entity id from who execute the action
     */
    private $sourceId;
    /**
     * @var $targetKind Kind
     * @description The entity who receive the action
     */
    private $targetKind;
    /**
     * @var $targetId string
     * @description The entity id who receive the action
     */
    private $targetId;
    /**
     * @var $eventType EventType
     * @description the event type that occurred like: create, update, view, export, see
     */
    private $eventType;
    /**
     * @var $triggerLocation string
     * @description the file where event is triggered
     */
    private $triggerLocation;
    /**
     * @var $place string
     * @description the service name that event occur
     */
    private $place;
    /**
     * @var $description string[]
     * @description the localized description of event map[string]string
     */
    private $description;

    /**
     * @var $context string[]
     * @description the event context map[string]string
     */
    private $context;

    private function __construct(){}

    /**
     * @param $serviceName string
     * @return AuditEvent
     * @throws AuditException
     */
    public static function start($serviceName) {
        // Unix time in Millisecond
        $eventTime = (int) round(microtime(true) * 1000);
        $ctx = AuditEvent::getContext();

        $event = new AuditEvent();
        $event->place = $serviceName;
        $event->triggerLocation = $ctx['file'] . ":" . $ctx['line'];
        $event->context = $ctx;
        $event->description = [];
        $event->timestamp = $eventTime;

        $event->addContext('file', $ctx['file']);
        $event->addContext('line', $ctx['line']);

        return $event;
    }

    /**
     * @return array
     */
    private static function getContext() {
        $stackLevel = 2;
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $context = [
            'file' => '',
            'line' => ''
        ];
        if (isset($debug[$stackLevel]) && $debug[$stackLevel]['file']) {
            $context['file'] = $debug[$stackLevel]['file'];
        }

        if (isset($debug[$stackLevel]) && $debug[$stackLevel]['line']) {
            $context['line'] = $debug[$stackLevel]['line'];
        }

        return $context;
    }

    /**
     * @param $id int
     * @return AuditEvent
     */
    public function withClient($id) {
        $this->clientId = $id;
        return $this;
    }

    /**
     * @param Kind $kind
     * @param $id
     * @return AuditEvent
     * @throws AuditException
     */
    public function withSource(kind $kind, $id) {

        if (empty($kind) || empty($id)) {
            throw new AuditException("Source Kind and Id can't be empty or null");
        }

        $this->sourceKind = $kind;
        $this->sourceId = strval($id);
        return $this;
    }

    /**
     * @param Kind $kind
     * @param $id
     * @return AuditEvent
     * @throws AuditException
     */
    public function onTarget(Kind $kind, $id) {

        if (empty($kind) || empty($id)) {
            throw new AuditException("Target Kind and Id can't be empty or null");
        }

        $this->targetKind = $kind;
        $this->targetId = strval($id);
        return $this;
    }

    /**
     * @param EventType $type
     * @throws AuditException
     * @return AuditEvent
     */
    public function withType(EventType $type) {

        if (empty($type)) {
            throw new AuditException("EventType and Id can't be empty or null");
        }

        $this->eventType = $type;
        return $this;
    }

    /**
     * @param $key string
     * @param $value string
     * @return AuditEvent
     * @throws AuditException
     */
    public function addContext($key, $value) {

        if (empty($key)) {
            throw new AuditException("Key can't be null in audit context");
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $this->context[$key] = strval($value);
        return $this;
    }

    /**
     * @param $locale
     * @param $description
     * @return AuditEvent
     * @throws AuditException
     */
    public function addDescription($locale, $description) {
        $allowedLocales = ["pt_BR", "en_US", "es_ES"];
        if (!in_array($locale, $allowedLocales)) {
            throw new AuditException(
                "Locale is invalid in addDescription on audit event, allowed locales: " . join(",", $allowedLocales)
            );
        }
        $this->description[$locale] = $description;
        return $this;
    }

    /**
     * @return string[]
     * @throws AuditException
     */
    public function asMap() {

        $requiredFields = [
            'clientId',
            'timestamp',
            'sourceKind',
            'sourceId',
            'targetKind',
            'targetId',
            'eventType',
            'triggerLocation',
            'place',
        ];

        foreach ($requiredFields as $rf) {
            if (empty($this->$rf)) {
                throw new AuditException("Required field ${$rf} is empty.");
            }
        }

        return [
            'client_id' => (int) $this->clientId,
            'timestamp' => (int) $this->timestamp,
            'source_kind' => $this->sourceKind->getValue(),
            'source_id' => $this->sourceId,
            'target_kind' => $this->targetKind->getValue(),
            'target_id' => $this->targetId,
            'event_type' => $this->eventType->getValue(),
            'trigger_location' => $this->triggerLocation,
            'place' => $this->place,
            'description' => $this->description,
            'context' => $this->context
        ];
    }
}
