<?php
namespace Wideti\DomainBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Wideti\DomainBundle\CircuitBreaker\CircuitBreakerServices\ServiceEnum;


/**
 * @ORM\Table("circuit_breaker", uniqueConstraints={@ORM\UniqueConstraint(name="unique_service", columns={"service"})})
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CircuitBreakerRepository")
 */
class CircuitBreaker
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="service", type="string", length=40, nullable=false)
     */
    private $service;

    /**
     * @ORM\Column(name="is_open", type="boolean", options={"default":0} )
     */
    protected $isOpen = false;

    /**
     * @ORM\Column(name="failure_threshold", type="integer", options={"default":5} )
     */
    protected $failureThreshold = 5;

    /**
     * @ORM\Column(name="failure_count", type="integer", options={"default":0} )
     */
    protected $failureCount = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="last_failure_time", type="datetime", length=25, nullable=true)
     */
    private $lastFailureTime = null;

    /**
     * @ORM\Column(name="reset_timeout", type="integer", options={"default":300} )
     */
    protected $resetTimeout = 300;

    /**
     * @ORM\Column(name="forced_open", type="boolean", options={"default":0} )
     */
    private $forcedOpen = false;

    /**
     * CircuitBreaker constructor.
     *
     * @param string $service
     * @throws InvalidArgumentException
     */
    public function __construct($service)
    {
        $this->validateService($service);
        $this->service = $service;
    }

    /**
     * Validates the service against the enum values.
     *
     * @param string $service
     * @throws InvalidArgumentException
     */
    private function validateService($service)
    {
        $validServices = array_values((new ReflectionClass('ServiceEnum'))->getConstants());
        if (!in_array($service, $validServices)) {
            throw new InvalidArgumentException("Invalid service: $service");
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param string $service
     */
    public function setService($service)
    {
        $this->validateService($service);
        $this->service = $service;
    }

    /**
     * @return bool
     */
    public function getIsOpen()
    {
        return $this->isOpen;
    }

    /**
     * @param bool $isOpen
     */
    public function setIsOpen($isOpen)
    {
        $this->isOpen = $isOpen;
    }

    /**
     * @return bool
     */
    public function isForcedOpen()
    {
        return $this->forcedOpen;
    }

    /**
     * @return int
     */
    public function getFailureCount()
    {
        return $this->failureCount;
    }

    /**
     * @param int $failureCount
     */
    public function setFailureCount($failureCount)
    {
        $this->failureCount = $failureCount;
    }

    /**
     * @return int
     */
    public function getResetTimeout()
    {
        return $this->resetTimeout;
    }

    /**
     * @param int $resetTimeout
     */
    public function setResetTimeout($resetTimeout)
    {
        $this->resetTimeout = $resetTimeout;
    }

    /**
     * @return int
     */
    public function getFailureThreshold()
    {
        return $this->failureThreshold;
    }

    /**
     * @param int $failureThreshold
     */
    public function setFailureThreshold($failureThreshold)
    {
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * @return \DateTime
     */
    public function getLastFailureTime()
    {
        return $this->lastFailureTime;
    }

    /**
     * @param \DateTime $lastFailureTime
     */
    public function setLastFailureTime($lastFailureTime)
    {
        $this->lastFailureTime = $lastFailureTime;
    }

    /**
     * @return bool
     */
    public function isCircuitOpen()
    {
        if ($this->isForcedOpen()) {
            return true;
        }

        if ($this->getLastFailureTime() != null) {
            $elapsedTime = time() - $this->getLastFailureTime()->getTimestamp();
            if ($elapsedTime >= $this->getResetTimeout()) {
                $this->reset();
            }
        } elseif ($this->getIsOpen()) {
            $this->reset();
        }

        return $this->getIsOpen();
    }

    /**
     * Report a failure to the circuit breaker.
     */
    public function reportFailure()
    {
        if (!$this->getLastFailureTime()){
            $this->setLastFailureTime(new \DateTime());
        }

        $failures = $this->getFailureCount();
        $failures++;

        $this->setFailureCount($failures);

        if ($failures > $this->getFailureThreshold()) {
            $this->openCircuit();
        }
    }

    /**
     * Open circuit breaker.
     */
    private function openCircuit()
    {
        $this->setIsOpen(true);
    }

    /**
     * Reset the circuit breaker.
     */
    public function reportSuccess() {
        $this->reset();
    }

    /**
     * Reset the circuit breaker.
     */
    private function reset()
    {
        $this->setIsOpen(false);
        $this->setFailureCount(0);
        $this->setLastFailureTime(null);
    }
}
