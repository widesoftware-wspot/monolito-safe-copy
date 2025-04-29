<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Repository\TemplateRepository;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;
use Wideti\DomainBundle\Repository\AccessPointsRepository;

class BuildAccessPointFromApiCreateDtoServiceImp implements BuildAccessPointFromApiCreateDtoService
{
    /**
     * @var AccessPointsGroupsRepository
     */
    private $accessPointsGroupsRepository;
    /**
     * @var TemplateRepository
     */
    private $templateRepository;
    /**
     * @var AccessPointsRepository
     */
    private $accessPointsRepository;

    public function __construct(
        TemplateRepository $templateRepository,
        AccessPointsGroupsRepository $accessPointsGroupsRepository,
        AccessPointsRepository $accessPointsRepository
    ) {
        $this->accessPointsGroupsRepository = $accessPointsGroupsRepository;
        $this->templateRepository = $templateRepository;
        $this->accessPointsRepository = $accessPointsRepository;
    }

    /**
     * @param CreateAccessPointDto $dto
     * @return AccessPoints
     */
    public function getEntity(CreateAccessPointDto $dto)
    {
        $accessPoint = new AccessPoints();
        $accessPoint->setIdentifier(null);
        $accessPoint->setLocal(null);
        $accessPoint->setTimezone(null);
        $accessPoint->setFriendlyName(null);
        $accessPoint->setVendor(null);
        $accessPoint->setStatus(null);

        if ($dto->getAction() == $dto::ACTION_CREATE) {
            $accessPoint = new AccessPoints();
            $accessPoint->setIdentifier($dto->getIdentifier());
            $accessPoint->setLocal($dto->getLocal());
            $accessPoint->setTimezone($dto->getTimezone());
            $accessPoint->setFriendlyName($dto->getFriendlyName());
            $accessPoint->setVendor($dto->getVendor());
            $accessPoint->setStatus($dto->getStatus());

            if ($dto->getClient()) {
                $accessPoint->setClient($dto->getClient());
            }

            $group = $this->getAccessPointGroup($dto->getGroupId());
            $accessPoint->setGroup($group);

            $template = $this->getTemplate($dto->getTemplateId());
            $accessPoint->setTemplate($template);

        } elseif ($dto->getAction() == $dto::ACTION_INTERNAL_CREATE) {
            $client = $dto->getClient();

            $accessPoint = new AccessPoints();
            $accessPoint->setIdentifier($dto->getIdentifier());
            $accessPoint->setLocal($dto->getLocal());
            $accessPoint->setTimezone($dto->getTimezone());
            $accessPoint->setFriendlyName($dto->getFriendlyName());
            $accessPoint->setVendor($dto->getVendor());

            if ($client) {
                $accessPoint->setClient($client);
            }

            $group = $this->accessPointsGroupsRepository
                ->findOneBy([
                    'client' => $client,
                    'isDefault' => true
                ]);

            $template = $this->templateRepository
                ->findOneBy([
                    'client' => $client,
                ], ['id' => 'ASC']);

            $accessPoint->setGroup($group);
            $accessPoint->setTemplate($template);
        } elseif ($dto->getAction() == $dto::ACTION_UPDATE) {
            $accessPoint = $this->accessPointsRepository->findOneBy([
                                'id' => $dto->getId(),
                                'client' => $dto->getClient()
                            ]);

            if ($dto->getFriendlyName() !== null) {
                $accessPoint->setFriendlyName($dto->getFriendlyName());
            }

            if ($dto->getLocal() !== null) {
                $accessPoint->setLocal($dto->getLocal());
            }

            if ($dto->getStatus() !== null) {
                $accessPoint->setStatus($dto->getStatus());
            }

            if ($dto->getTemplateId() !== null) {
                $template = $this->getTemplate($dto->getTemplateId());
                $accessPoint->setTemplate($template);
            }

            if ($dto->getGroupId() !== null) {
                $group = $this->getAccessPointGroup($dto->getGroupId());

                $accessPoint->setGroup($group);
            }
        }

        return $accessPoint;
    }

    /**
     * @param int $templateId
     * @return Template
     */
    private function getTemplate($templateId = null)
    {
        if (!$templateId || !is_int($templateId)) return null;

        /** @var Template $template */
        $template = $this
            ->templateRepository
            ->findOneBy([
                'id' => $templateId
            ]);
        return $template;
    }

    /**
     * @param int $groupId
     * @return AccessPointsGroups
     */
    private function getAccessPointGroup($groupId = null)
    {
        if (!$groupId || !is_int($groupId)) return null;

        /** @var AccessPointsGroups $group */
        $group = $this
            ->accessPointsGroupsRepository
            ->findOneBy([
            "id" => $groupId
        ]);

        return $group;
    }
}
