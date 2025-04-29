<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Exception\PhoneFieldNotFoundException;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\SmsMarketing\Dto\TotalGuestsFilter;

class SmsMarketingHelper
{
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    /**
     * SmsMarketingHelper constructor.
     * @param CustomFieldsService $customFieldsService
     */
    public function __construct(CustomFieldsService $customFieldsService)
    {
        $this->customFieldsService = $customFieldsService;
    }

    /**
     * @param $filter
     * @param $clientDomain
     * @return TotalGuestsFilter
     * @throws PhoneFieldNotFoundException
     */
    public function prepareTotalGuestFilter($filter, $clientDomain)
    {
        $mobileField    = $this->customFieldsService->checkIfIsPhoneOrMobileCustomField();
        $group          = isset($filter["group"]) ? $filter["group"] : "";
        $ddd            = isset($filter["ddd"]) ? $filter["ddd"] : "";
        $dateFrom       = isset($filter["dateFrom"]) ? $filter["dateFrom"] : "";
        $dateTo         = isset($filter["dateTo"]) ? $filter["dateTo"] : "";

        if (!$mobileField) {
            throw new PhoneFieldNotFoundException();
        }

        return new TotalGuestsFilter($clientDomain, $mobileField, $group, $ddd, $dateFrom, $dateTo);
    }
}
