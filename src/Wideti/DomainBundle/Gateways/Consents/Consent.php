<?php


namespace Wideti\DomainBundle\Gateways\Consents;


use Exception;

class Consent
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var int
     */
    private $clientId;
    /**
     * @var int
     */
    private $version;
    /**
     * @var string
     */
    private $status;

    /**
     * @var Condition[]
     */
    private $conditions;

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
     * @return Consent
     */
    public static function create($id) {
        return new Consent($id);
    }

    /**
     * @param Exception $err
     * @return Consent
     */
    public function withError(Exception $err) {
        $this->hasError = true;
        $this->error = $err;
        return $this;
    }

    /**
     * @param int $clientId
     * @return Consent
     */
    public function withClientId($clientId) {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @param int $version
     * @return Consent
     */
    public function withVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * @param $status
     * @return Consent
     */
    public function withStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * @param Condition $condition
     * @return Consent
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
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Condition[]
     */
    public function getConditions()
    {
        return $this->conditions;
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
