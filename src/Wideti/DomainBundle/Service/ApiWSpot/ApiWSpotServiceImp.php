<?php

namespace Wideti\DomainBundle\Service\ApiWSpot;

use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\ApiWSpotContracts;
use Wideti\DomainBundle\Entity\ApiWSpotResources;
use Wideti\DomainBundle\Entity\ApiWSpotRoles;
use Wideti\DomainBundle\Entity\Client;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Symfony\Component\HttpFoundation\Request;

class ApiWSpotServiceImp implements ApiWSpotService
{
    const CONTRACT_KEY   = 'requestsPerMonth';
    const CONTRACT_VALUE = 10000;

    use EntityManagerAware;
    use SecurityAware;

    public function create(ApiWSpot $token, $resourceNames)
    {
        if (!$token->getToken()) {
            $token->setToken($this->generateToken());
        }
        $this->persist($token);

        $this->createRole($token, ApiWSpotRoles::ROLE_API);
        $this->createContract($token);
        $this->createResourcePermission($token, $resourceNames);
    }

    /**
     * @param Client $client
     */
    public function createSegmentationTokenViaBluePanel(Client $client)
    {
        $hasSegmentationToken = $this->em
            ->getRepository('Wideti\DomainBundle\Entity\ApiWSpot')
            ->getByResourceName($client, 'segmentation');

        if (!$hasSegmentationToken) {
            $token = new ApiWSpot();
            $token->setClient($client);
            $token->setName("Segmentação - WSpot");
            $token->setPermissionType(ApiWSpotRoles::ROLE_WRITE);
            $this->create($token, ['segmentation']);
        }
    }

    private function findAndRemoveResources(ApiWSpot $token, $resourcesToDelete) {

        foreach ($token->getResources() as $resource) {
            if (array_key_exists($resource->getResource(), $resourcesToDelete) && in_array($resource->getMethod(), $resourcesToDelete[$resource->getResource()])) {
                $this->em->remove($resource);
                $this->em->flush();
            }
        }
    }

    private function createResources(ApiWSpot $token, $resourcesToCreate) {
        foreach ($resourcesToCreate as $key => $methods) {
            foreach ($methods as $method) {
                $resource = new ApiWSpotResources();
                $resource->setApi($token);
                $resource->setResource($key);
                $resource->setMethod($method);
                $this->persist($resource);
            }
        }
    }

    public function update(ApiWSpot $token, $resourceNames)
    {
        $resourcesFromForm = $this->getResourcePermissions($token, $resourceNames);
        $existentResources = [];
        $resourcesToDelete = [];
        $resourcesToCreate = [];
        foreach ($token->getResources() as $resource) {
            $existentResources[$resource->getResource()][] = $resource->getMethod();
        }
        
        foreach ($resourcesFromForm as $r => $methods) {
            foreach($methods as $method) {
                if (!array_key_exists($r, $existentResources) || !in_array($method, $existentResources[$r])) {
                    $resourcesToCreate[$r][] = $method;
                }
            }
        }
        foreach ($existentResources as $r => $methods) {
            foreach($methods as $method) {
                if (!array_key_exists($r, $resourcesFromForm) || !in_array($method, $resourcesFromForm[$r])) {
                    $resourcesToDelete[$r][] = $method;
                }
            }
        }

        $this->findAndRemoveResources($token, $resourcesToDelete);
        $this->createResources($token, $resourcesToCreate);                                             
    }

    public function delete(ApiWSpot $token)
    {
        $this->em->remove($token);
        $this->em->flush();
    }

    public function generateToken()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    public function createRole(ApiWSpot $token, $rolePermission)
    {
        $role = new ApiWSpotRoles();
        $role->setApi($token);
        $role->setRole($rolePermission);
        $this->persist($role);
    }

    public function getResourcePermissions(ApiWSpot $token, array $resourceNames)
    {
        $resources = [];
        $permissionType = $token->getPermissionType();
        $methods = [];
        if ($permissionType == ApiWSpotRoles::ROLE_READ) {
            $methods = [
                'GET'
            ];
        }

        if ($permissionType == ApiWSpotRoles::ROLE_WRITE) {
            $methods = [
                'GET',
                'POST',
                'PUT',
                'DELETE'
            ];
        }

        foreach ($resourceNames as $key => $value) {
            $resources[$value] = [];
            foreach ($methods as $method) {
                $resources[$value][] = $method; 
            }
        }

        return $resources;
    }

    public function createResourcePermission(ApiWSpot $token, array $resourceNames)
    {
        $permissionType = $token->getPermissionType();

        $methods = [];

        if ($permissionType == ApiWSpotRoles::ROLE_READ) {
            $methods = [
                'GET'
            ];
        }

        if ($permissionType == ApiWSpotRoles::ROLE_WRITE) {
            $methods = [
                'GET',
                'POST',
                'PUT',
                'DELETE'
            ];
        }

        foreach ($resourceNames as $key => $value) {
            foreach ($methods as $method) {
                $resource = new ApiWSpotResources();
                $resource->setApi($token);
                $resource->setResource($value);
                $resource->setMethod($method);
                $this->persist($resource);
            }
        }
    }

    public function createContract(ApiWSpot $token)
    {
        $contract = new ApiWSpotContracts();
        $contract->setApi($token);
        $contract->setKey(self::CONTRACT_KEY);
        $contract->setValue((int) self::CONTRACT_VALUE);
        $this->persist($contract);
    }

    public function getTokenByResourceName(Client $client, $resourceName)
    {
        return $this->em
            ->getRepository('Wideti\DomainBundle\Entity\ApiWSpot')
            ->getByResourceName($client, $resourceName);
    }

    private function removeResouces(ApiWSpot $token)
    {
        foreach ($token->getResources() as $resource) {
            $this->em->remove($resource);
        }
    }

    private function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }
}
