<?php

namespace Wideti\DomainBundle\Service\Social\Facebook;

use Wideti\DomainBundle\Service\Cache\CacheServiceAware;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;

class FacebookCsrfFixService
{
    use FacebookPersistentDataHandlerAware;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;

	/**
	 * FacebookCsrfFixService constructor.
	 * @param CacheServiceImp $cacheService
	 */
	public function __construct(CacheServiceImp $cacheService)
	{
		$this->cacheService = $cacheService;
	}

	public function saveCSRFOnCache($loginUrl)
    {
        if (empty($loginUrl)) {
            return false;
        }

        $parameters = $this->getParametersFromUrl($loginUrl);
        if ($this->cacheService->isActive()) {
            $this->cacheService->set($parameters['state'], $parameters['state'], 1800);
        }
        return true;
    }

    public function transferCSRFCacheToSession($stateCode)
    {

        if ($this->facebookPersistentDataHandler->get('state')) {
            return;
        }

        if ($this->cacheService->isActive()) {
            $stateCode = $this->cacheService->get($stateCode);
            $this->facebookPersistentDataHandler->set('state', $stateCode);
        }
    }

    private function getParametersFromUrl($url)
    {
        $urlSplit = explode("?", $url);
        $parametersRaw = explode("&", $urlSplit[1]);

        $parameters = [];
        foreach ($parametersRaw as $param) {
            $values = explode("=", $param);
            $parameters[$values[0]] = $values[1];
        }

        return $parameters;
    }

}