<?php

namespace Wideti\DomainBundle\Service\CustomFields;
use Wideti\DomainBundle\Service\Cache\CacheService;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

/**
 * Class CustomFieldsCacheService
 * @package Wideti\DomainBundle\Service\CustomFields
 */
class CustomFieldsCacheService
{
    use TwigAware;

    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * CustomFieldsCacheService constructor.
     * @param CacheService $cacheService
     */
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function clear()
    {
        $isCacheActive = $this->isActive();

        if ($isCacheActive) {
            $this->cacheService->remove('custom_fields');
        }

        return $this->render('AdminBundle:CustomFields:cacheClear.html.twig', [
            'isCacheActive' => $isCacheActive
        ]);
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->cacheService->isActive();
    }

    /**
     * @param $key
     */
    public function removeByKey($key)
    {
        $this->cacheService->removeByKey($key);
    }
}