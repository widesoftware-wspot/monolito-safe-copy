<?php

namespace Wideti\DomainBundle\Service\CampaignCallToAction;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\CallToActionAccessData;
use Wideti\DomainBundle\Repository\CampaignCallToAction\AccessDataRepository;
use Wideti\FrontendBundle\Factory\Nas;

/**
 * Class CreateAccessDataService
 * @package Wideti\DomainBundle\Service\CampaignCallToAction
 */
class CreateAccessDataService
{
    /**
     * @var AccessDataRepository
     */
    private $accessDataRepository;

    /**
     * CreateAccessDataService constructor.
     * @param AccessDataRepository $accessDataRepository
     */
    public function __construct(AccessDataRepository $accessDataRepository)
    {
        $this->accessDataRepository = $accessDataRepository;
    }

    /**
     * @param Request $request
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Request $request)
    {
        $accessData = new CallToActionAccessData();

        $accessData
            ->setCampaign(
                $this->accessDataRepository->getCampaignById($request->get('campaign'))
            )
            ->setType($request->get('type'))
            ->setMacAddress($request->get('macAddress'))
            ->setGuestId($request->get('guest'))
            ->setUrl(urldecode($request->get('url')))
            ->setApMacAddress($request->get('apMacAddress'));

        $this->accessDataRepository->save($accessData);
    }

	public function updateCallToActionGuestId(Guest $guest, Nas $nas = null, $campaignId)
	{
		$result = $this->accessDataRepository->getGuestEqualZero($nas->getGuestDeviceMacAddress(), $campaignId);

		if ($result) {
			$result->setGuestId($guest->getMysql());
			$this->accessDataRepository->save($result);
		}
	}
}
