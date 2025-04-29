<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;

class WhiteLabelRepository extends EntityRepository
{
	use ContainerAwareTrait;

	public function findWhiteLabelMap(RequestStack $requestStack)
	{
		$cache = $this->container->get('core.service.cache');

		if (!$requestStack->getCurrentRequest() instanceof Request) {
			return [];
		}

		$clientDomain =  $requestStack->getCurrentRequest()->getHost();
        if(strpos( $clientDomain, "wspot.com.br") || strpos($clientDomain, "mambowifi")){
            $arrayDomain    = explode(".", $requestStack->getCurrentRequest()->getHost());
            $clientDomain   = reset($arrayDomain);
        }

		if (!$cache->isActive()) {
			return $this->getWhiteLabel($clientDomain);
		}

		try {
			if ($cache->exists(CacheServiceImp::WHITE_LABEL) !== 1) {
				$results = $this->getWhiteLabel($clientDomain);
				$cache->set(
					CacheServiceImp::WHITE_LABEL,
					$results,
					CacheServiceImp::TTL_WHITE_LABEL
				);
			}
			$config = $cache->get(CacheServiceImp::WHITE_LABEL);

			if (!is_array($config) || empty($config)) {
				throw new \Exception("White Label from cache is empty");
			}

			return $config;

		} catch (\Exception $e) {
			return $this->getWhiteLabel($clientDomain);
		}
	}

	/**
	 * @param $client
	 * @return array
	 */
	public function getWhiteLabel($clientDomain)
	{
		/**
		 * @var Client $client
		 */
		$client = $this->getEntityManager()
			->getRepository('DomainBundle:Client')
			->findOneByDomain($clientDomain);

		$entity = $this->getEntityManager()
			->getRepository('DomainBundle:WhiteLabel')
			->findOneBy(['client' => $client]);

		if (!$entity) {
			return [];
		}

		$logotipo = $entity->getLogotipo();
		if (strpos($logotipo, '/bundles/admin/') === false && strpos($logotipo, 'http') === false) {
			$awsBucketName = $this->container->getParameter('aws_bucket_proxy_name');
			$awsFolderName = $client->getDomain();
			$logotipo = "https://{$awsBucketName}/{$awsFolderName}/{$logotipo}";
		}

		$result = [
			'companyName'   => $entity->getCompanyName(),
			'panelColor'    => $entity->getPanelColor(),
			'logotipo'      => $logotipo,
			'signature'     => str_replace('{ano}', date('Y'), $entity->getSignature())
		];

		return $result;
	}
}
