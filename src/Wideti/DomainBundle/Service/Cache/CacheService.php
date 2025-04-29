<?php
namespace Wideti\DomainBundle\Service\Cache;

interface CacheService
{
    public function isActive();
    public function set($key, $value, $timeToLive = null, $useWspotPrefix = true, $jsonEncode = false);
    public function get($key);
    public function exists($key);
    public function remove($key);
    public function removeAll();
}
