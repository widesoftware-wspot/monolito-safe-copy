<?php

namespace Wideti\ApiBundle\Service;

use Gedmo\Exception\InvalidArgumentException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsService;
use Wideti\DomainBundle\Service\Sms\Dto\SmsBuilder;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\FrontendBundle\Factory\NasHandlers\NasBuilder;

/**
 * Class GuestApiService
 * @package Wideti\ApiBundle\Service
 */
class GuestApiService
{
    /**
     * @var SmsService
     */
    private $smsService;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    /**
     * GuestApiService constructor.
     * @param SmsService $smsService
     * @param ConfigurationService $configurationService
     */
    public function __construct(
        SmsService           $smsService,
        ConfigurationService $configurationService,
        CustomFieldsService  $customFieldsService
    )
    {
        $this->smsService           = $smsService;
        $this->configurationService = $configurationService;
        $this->customFieldsService  = $customFieldsService;
    }

    /**
     * @return array
     */
    public function getPhoneFields()
    {
        return ['phone', 'mobile'];
    }

    /**
     * @param Guest $guest
     * @param $client
     * @param $locale
     * @throws \Exception
     */
    public function sendSMS(Guest $guest, $client, $locale)
    {
        $this->smsService->send($this->structureMessage($guest, $client, $locale), $guest);
    }

    /**
     * @return bool
     */
    public function hasPhoneField()
    {
        $phoneFields = $this->getPhoneFields();

        foreach ($phoneFields as $phoneField) {
            if ($this->customFieldsService->getFieldByNameType($phoneField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Guest $guest
     * @return \Wideti\FrontendBundle\Factory\Nas
     */
    private function createSimulatedNAS(Guest $guest)
    {
        $nasBuilder = new NasBuilder();
        $nasFormPostParameter = new NasFormPostParameter(false, false, false, false);

        return $nasBuilder->withAccessPointMacAddress($guest->getRegistrationMacAddress())
            ->withNasUrlPost($nasFormPostParameter)
            ->withVendorRawParameters([])
            ->withExtraParams([])
            ->withVendorName('')
            ->withGuestDeviceMacAddress('')
            ->build();
    }

    /**
     * @param Guest $guest
     * @param $client
     * @param $locale
     * @return mixed
     * @throws \Exception
     */
    private function structureMessage(Guest $guest, $client, $locale)
    {
        $localeList = ["pt", "en", "es"];

        if (!in_array($locale, $localeList)) {
            throw new InvalidArgumentException("{$locale} não é uma localização válida para envio de SMS");
        }

        $message = $this->configurationService->get(
            $this->createSimulatedNAS($guest),
            $client,
            "content_welcome_sms_{$locale}"
        );

        if ($message) {
            $loginField = $this->customFieldsService->getLoginField();

            $message = str_replace(
                ["{{ user }}", "{{ password }}"],
                [$guest->getProperties()[$loginField[0]->getIdentifier()], $guest->getPassword()],
                $message
            );
        }

        $builder = new SmsBuilder();

        return $builder
                ->withContent($message)
                ->withType(SmsDto::WELCOME)
                ->build();
    }
}