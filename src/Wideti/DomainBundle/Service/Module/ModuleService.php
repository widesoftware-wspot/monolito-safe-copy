<?php

namespace Wideti\DomainBundle\Service\Module;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Module;
use Wideti\DomainBundle\Entity\ModuleConfigurationValue;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;

class ModuleService
{
    use EntityManagerAware;
    use FlashMessageAware;
    use SecurityAware;
    use LoggerAware;

    protected $modules = [
        'access_code'         => 'enable_access_code',
        'free_access'         => 'enable_free_access',
        'blacklist'           => 'enable_blacklist',
        'business_hours'      => 'enable_business_hours',
        'white_label'         => 'enable_white_label',
        'smart_location'      => 'enable_smart_location',
        'deskbee_integration' => 'enable_deskbee_integration',
        'hubsoft_integration' => 'enable_hubsoft_integration',
        'Ixc_integration'     => 'enable_Ixc_integration'

    ];

    public function modulePermission($shortCode)
    {
        $client = $this->getLoggedClient();

        $clientModule = $this->em
            ->getRepository('DomainBundle:Module')
            ->checkClientModule($shortCode, $client->getId());

        return (bool) $clientModule;
    }

    public function checkModuleIsActive($shortCode, $client = null)
    {
        if ($client == null) {
            $client = $this->getLoggedClient();
        }

        if (!array_key_exists($shortCode, $this->modules)) {
            return false;
        }

        $module = $this->em
            ->getRepository('DomainBundle:ModuleConfigurationValue')
            ->findByModuleConfigurationKey($client->getId(), $this->modules[$shortCode]);

        if ($module === null) {
            // $this->logger->addCritical('Module ' . $shortCode . ' not found');
            return false;
        }

        return boolval($module->getValue());
    }

    public function getClientModules()
    {
        $client = $this->getLoggedClient();
        $listModules = [];
        if (!is_null($client)){
            $listModulesClient = $this->em
                ->getRepository('DomainBundle:Module')
                ->findClientModule($client);

            foreach ($listModulesClient as $module){
                $listModules[] = $module->getShortCode();
            }
        }
        return $listModules;
    }

    public function enableOrDisableModule(ModuleConfigurationValue $entity, $status)
    {
        $entity->setValue(($status == 'enable') ? true : false);
        $this->em->persist($entity);
        $this->em->flush();
    }
}
