<?php
namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\CircuitBreaker;


/**
 * CircuitBreakerRepository
 */
class CircuitBreakerRepository extends EntityRepository
{
    /**
     * Find a CircuitBreaker entity by its service name.
     *
     * @param string $service
     * @return null|CircuitBreaker
     */
    public function findByService($service)
    {
        return $this->findOneBy(['service' => $service]);
    }

    /**
     * Check CircuitBreaker by its service name.
     *
     * @param string $service
     * @return bool
     */
    public function CheckCircuitIsOpen($service)
    {
        $circuitBreaker = $this->findByService($service);
        if (!$circuitBreaker){
            return false;
        }

        $isOpen = $circuitBreaker->isCircuitOpen();
        $this->save($circuitBreaker);
        return $isOpen;
    }

    /**
     * Save or update a CircuitBreaker entity.
     *
     * @param CircuitBreaker $circuitBreaker
     */
    public function save(CircuitBreaker $circuitBreaker)
    {
        $this->_em->persist($circuitBreaker);
        $this->_em->flush();
    }

    /**
     * @param string $service
     */
    public function reportFailure($service)
    {
        $circuitBreaker = $this->findByService($service);
        if ($circuitBreaker instanceof CircuitBreaker) {
            $circuitBreaker->reportFailure();
            $this->save($circuitBreaker);
        }
    }

    /**
     * Open circuit breaker for a specific service.
     *
     * @param string $service
     */
    public function openCircuit($service)
    {
        $circuitBreaker = $this->findByService($service);
        if ($circuitBreaker instanceof CircuitBreaker) {
            $circuitBreaker->openCircuit();
            $this->save($circuitBreaker);
        }
    }

    /**
     * Reset circuit breaker on a specific service.
     *
     * @param string $service
     */
    public function resetCircuit($service)
    {
        $circuitBreaker = $this->findByService($service);
        if ($circuitBreaker instanceof CircuitBreaker) {
            $circuitBreaker->reset();
            $this->save($circuitBreaker);
        }
    }
}
