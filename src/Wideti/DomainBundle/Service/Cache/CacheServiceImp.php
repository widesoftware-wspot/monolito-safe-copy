<?php
namespace Wideti\DomainBundle\Service\Cache;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\DomainBundle\Service\ClientSelector\ClientSelectorServiceAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class CacheServiceImp implements CacheService
{
    const TEMPLATE_MODULE                   = 'template';

    const TEMPLATE_DEFAULT                  = 'template:default';
    const TEMPLATE_BY_CAMPAIGN              = 'template:byCampaign';
    const TEMPLATE_BY_ACCESS_POINT          = 'template:byAccessPoint';
    const TEMPLATE_BY_ACCESS_POINT_GROUP    = 'template:byAccessPointGroup';

    const LOGIN_FIELD                       = 'login_field';
    const CUSTOM_FIELDS                     = 'custom_fields';

    const DASHBOARD_MODULE                  = 'dashboard';
    const DASHBOARD_VISITS_REGISTERS_PER_DAY    = 'dashboard:visitsPerDay';
    const DASHBOARD_MOST_ACCESSED_APS       = 'dashboard:mostAccessedAps';
    const DASHBOARD_MOST_ACCESSED_HOURS     = 'dashboard:mostAccessedHours';
    const DASHBOARD_SIGNUPS_ORIGIN          = 'dashboard:signupsOrigin';
    const DASHBOARD_ACCESS_DATA             = 'dashboard:accessData';
    const DASHBOARD_CHECKINS                = 'dashboard:checkins';
    const DASHBOARD_AVERAGE_CONNECTION_TIME = 'dashboard:averageConnectionTime';
    const DASHBOARD_MOST_TRAFFIC_APS        = 'dashboard:mostTrafficAps';
    const DASHBOARD_DOWNLOAD_UPLOAD         = 'dashboard:downloadUpload';

    const REPORT_ACCESS_POINTS              = 'report:accessPointsReport';

    const WHITE_LABEL                       = 'whiteLabel';

    const TTL_CUSTOM_FIELDS                 = 3600;
    const TTL_CONFIGURATIONS                = 3600;
    const TTL_WHITE_LABEL                   = 3600;
    const TTL_AP_NOT_REGISTERED             = 3600;
    const TTL_NAS                           = 600;

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
        return unserialize($this->cache->get($this->slug() . $key));
    }

    public function getTTL($key)
    {
        if ($this->cacheActive) {
            return $this->cache->ttl($this->slug() . $key);
        }
    }


    public function getKeysByValue($value)
    {
        return $this->cache->keys($value);
    }

    public function getAllKeys()
    {
        return $this->cache->keys('*');
    }

    public function exists($key)
    {
        return $this->cache->exists($this->slug() . $key);
    }

    public function remove($key)
    {
        return $this->cache->del($this->slug() . $key);
    }

    public function removeAll()
    {
        $this->cache->flushall();
    }

    public function removeAllByModule($module)
    {
        if ($module == CacheServiceImp::TEMPLATE_MODULE) {
            $keys = $this->cache->keys($this->slug() . CacheServiceImp::TEMPLATE_MODULE . '*');

            foreach ($keys as $key) {
                $this->cache->del($key);
            }
        }

        if ($module == CacheServiceImp::DASHBOARD_MODULE) {
            $this->cache->del($this->slug() . CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY);
        }

        return true;
    }

    public function removeAllConfigs($allClients = false)
    {
        $keys = $this->cache->keys("{$this->slug()}config_*");

        if ($allClients) {
            $keys = $this->cache->keys("*config*");
        }

        foreach ($keys as $key) {
            $this->cache->del($key);
        }

        return true;
    }

    public function removeByKey($value)
    {
        if ($this->cacheActive) {
            $this->cache->del($value);
        }
    }

    public function removeCustom($string)
    {
        $keys = $this->cache->keys($this->slug() . $string . "*");

        foreach ($keys as $key) {
            $this->cache->del($key);
        }

        return true;
    }
}
