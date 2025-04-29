<?php

namespace Wideti\DomainBundle\Twig;

class AllowedModuleByVendor extends \Twig_Extension
{
    const MIKROTIK = 'mikrotik';

    const MODULE_SITES_BLOCKING = 'sites_blocking';
    const MODULE_AP_MONITORING  = 'access_point_monitoring';

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('allowedModuleByVendor', array($this, 'isAuthorized')),
        );
    }

    public function isAuthorized($vendor, $module)
    {
        $modulesWithVendors = $this->modulesWithVendorsRule();

        return array_key_exists($module, $modulesWithVendors) && in_array($vendor, $modulesWithVendors[$module]);
    }

    private function modulesWithVendorsRule()
    {
        return [
            self::MODULE_SITES_BLOCKING => [
                self::MIKROTIK
            ],
            self::MODULE_AP_MONITORING => [
                self::MIKROTIK
            ]
        ];
    }

    public function getName()
    {
        return 'allowed_module_by_vendor';
    }
}
