<?php
namespace Wideti\DomainBundle\Service\Cache;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\DomainBundle\Service\ClientSelector\ClientSelectorServiceAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class PolicyCacheServiceImp implements CacheService
{
    const RADIUS_POLICY_KEY = 'wspot:radius:policy:';
    const TTL_RADIUS_POLICY = 86400;

    use ClientSelectorServiceAware;
    use LoggerAware;
    use SessionAware;

    protected $cacheActive;
    protected $host;
    protected $port;
    protected $cache;

    public function __construct($cacheActive, $host, $port)
    {
        $this->cacheActive  = $cacheActive;
        $this->host         = $host;
        $this->port         = $port;

        if ($this->cacheActive) {
            try {
                $this->cache = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host'   => $this->host,
                    'port'   => $this->port
                ]);
                $this->cache->ping();
            } catch (\Exception $exception) {
                $this->cacheActive = false;
            }
        }
    }

	public function isActive()
	{
		return $this->cacheActive;
	}

    private function slug()
    {
        if (!$this->getLoggedClient()) {
            try {
                $this->clientSelectorService->define($_SERVER['HTTP_HOST']);
            } catch (NotFoundHttpException $e) {
                throw new NotFoundHttpException();
            }
        }

        $client = $this->getLoggedClient();

        return 'wspot:' . $client->getDomain() . ':';
    }

    /**
     * @param $key
     * @param $value
     * @param null $timeToLive
     * @param bool $useWspotPrefix
     * @param bool $jsonEncode
     */
    public function set($key, $value, $timeToLive = null, $useWspotPrefix = true, $jsonEncode = false)
    {
        if ($useWspotPrefix) {
            $key = $this->slug() . $key;
        }

        if ($jsonEncode) {
            $value = json_encode($value);
        } else {
            $value = serialize($value);
        }

        $this->cache->set($key, $value);

        if ($timeToLive) {
            $this->cache->expire($key, $timeToLive);
        }
    }

    public function get($key)
    {
        return $this->cache->get($key);
    }

    public function exists($key)
    {
        return $this->cache->exists($key);
    }

    public function remove($key)
    {
        return $this->cache->del([$key]);
    }

    public function removeAll()
    {
        $this->cache->flushall();
    }
}
