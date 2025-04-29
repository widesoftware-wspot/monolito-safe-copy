<?php


namespace Wideti\DomainBundle\Gateways\Consents;


use Exception;

class Signature
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var int
     */
    private $entityId;
    /**
     * @var string
     */
    private $entityType;
    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $consentId;

    /**
     * @var bool
     */
    private $hasError;

    /**
     * @var Exception
     */
    private $error;

    /**
     * Consent constructor.
     * @param string $id
     */
    private function __construct($id) {
        $this->hasError = false;
        $this->conditions = [];
        $this->id = $id;
    }

    /**
     * @param string $id
     * @return Signature
     */
    public static function create($id) {
        return new Signature($id);
    }

    /**
     * @param Exception $err
     * @return Signature
     */
    public function withError(Exception $err) {
        $this->hasError = true;
        $this->error = $err;
        return $this;
    }

    /**
     * @param int $entityId
     * @return Signature
     */
    public function withEntityId($entityId) {
        $this->entityId = $entityId;
        return $this;
    }

    public function withEntityType($entityType) {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * @param string $consentId
     * @return Signature
     */
    public function withConsentId($consentId) {
        $this->consentId = $consentId;
        return $this;
    }

    /**
     * @param $status
     * @return Signature
     */
    public function withStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * @param Condition $condition
     * @return Signature
     */
    public function addCondition(Condition $condition) {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getConsentId()
    {
        return $this->consentId;
    }


    /**
     * @return bool
     */
    public function getHasError()
    {
        return $this->hasError;
    }

    /**
     * @return Exception
     */
    public function getError()
    {
        return $this->error;
    }
}
