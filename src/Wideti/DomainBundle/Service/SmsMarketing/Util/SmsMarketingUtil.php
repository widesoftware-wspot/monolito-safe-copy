<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Util;

use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Service\SmsMarketing\Builder\SmsMarketingBuilder;

class SmsMarketingUtil
{
    public static function convertToObject(array $array)
    {
        return SmsMarketingBuilder::getBuilder()
            ->withId($array["id"])
            ->withClientId($array["clientId"])
            ->withAdminUserId($array["adminUserId"])
            ->withStatus($array["status"])
            ->withLotNumber($array["lotNumber"])
            ->withQuery($array["query"])
            ->withTotalSms($array["totalSms"])
            ->withUrlShortnedType($array["urlShortnedType"])
            ->withUrlShortned($array["urlShortned"])
            ->withUrlShortnedHash($array["urlShortnedHash"])
            ->withMessage($array["message"])
            ->withCreatedAt(DateTimeHelper::defineAsUTC($array["createdAt"]))
            ->withSentAt(DateTimeHelper::defineAsUTC($array["sentAt"]))
            ->withUpdatedAt(DateTimeHelper::defineAsUTC($array["updatedAt"]))
            ->build();
    }
}
