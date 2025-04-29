<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Dto\CampaignDto;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignMediaImage;
use Wideti\DomainBundle\Entity\CampaignMediaVideo;

class CampaignDtoHelper
{
    public static function convert(Campaign $campaign)
    {
        $dto = new CampaignDto();
        $dto->setId($campaign->getId());
        $dto->setStatus($campaign->getStatus());
        $dto->setName($campaign->getName());
        $dto->setStartDate($campaign->getStartDate());
        $dto->setEndDate($campaign->getEndDate());
        $dto->setBgColor($campaign->getBgColor());
        $dto->setRedirectUrl($campaign->getRedirectUrl());

        $dto->setHours($campaign->getCampaignHours());
        $dto->setCallToAction($campaign->getCallToAction());

        $dto->setPreLogin(false);
        $dto->setPosLogin(false);

        $images = $campaign->getCampaignMediaImage();
        $videos = $campaign->getCampaignMediaVideo();

        $count_image_pre = 0;
        $count_image_pos = 0;
        /**
         * @var CampaignMediaImage $image
         */
        foreach ($images as $image) {
            if ($image->getStep() == Campaign::STEP_PRE_LOGIN) {
                $dto->setPreLogin(true);
                $dto->setPreLoginMediaType(CampaignDto::MEDIA_IMAGE);
                $dto->setPreLoginFullSize($image->getFullSize());
                $dto->setPreLoginMediaDesktop($image->getImageDesktop());
                $dto->setPreLoginMediaDesktop2($image->getImageDesktop2());
                $dto->setPreLoginMediaDesktop3($image->getImageDesktop3());

                $dto->setPreLoginMediaMobile($image->getImageMobile());
                $dto->setPreLoginMediaMobile2($image->getImageMobile2());
                $dto->setPreLoginMediaMobile3($image->getImageMobile3());

                $image->getImageMobile()  ? $count_image_pre++ : null;
                $image->getImageMobile2() ? $count_image_pre++ : null;
                $image->getImageMobile3() ? $count_image_pre++ : null;
                $dto->setPreLoginMediaTime($image->getExhibitionTime() * $count_image_pre);
            } else {
                $dto->setPosLogin(true);
                $dto->setPosLoginMediaType(CampaignDto::MEDIA_IMAGE);
                $dto->setPosLoginFullSize($image->getFullSize());
                $dto->setPosLoginMediaDesktop($image->getImageDesktop());
                $dto->setPosLoginMediaDesktop2($image->getImageDesktop2());
                $dto->setPosLoginMediaDesktop3($image->getImageDesktop3());

                $dto->setPosLoginMediaMobile($image->getImageMobile());
                $dto->setPosLoginMediaMobile2($image->getImageMobile2());
                $dto->setPosLoginMediaMobile3($image->getImageMobile3());

                $image->getImageMobile()  ? $count_image_pos++ : null;
                $image->getImageMobile2() ? $count_image_pos++ : null;
                $image->getImageMobile3() ? $count_image_pos++ : null;
                $dto->setPosLoginMediaTime($image->getExhibitionTime() * $count_image_pos);
            }
        }

        /**
         * @var CampaignMediaVideo $video
         */
        foreach ($videos as $video) {
            if ($video->getStep() == Campaign::STEP_PRE_LOGIN) {
                $dto->setPreLogin(true);
                $dto->setPreLoginMediaType(CampaignDto::MEDIA_VIDEO);
                $dto->setPreLoginFullSize(true);
                $dto->setPreLoginMediaDesktop($video->getUrl());
                $dto->setPreLoginMediaMobile($video->getUrl());
                $dto->setPreLoginMp4Media($video->getUrlMp4());
                $dto->setPreLoginOrientation($video->getOrientation());
            } else {
                $dto->setPosLogin(true);
                $dto->setPosLoginMediaType(CampaignDto::MEDIA_VIDEO);
                $dto->setPosLoginFullSize(true);
                $dto->setPosLoginMediaDesktop($video->getUrl());
                $dto->setPosLoginMediaMobile($video->getUrl());
                $dto->setPosLoginMp4Media($video->getUrlMp4());
                $dto->setPosLoginOrientation($video->getOrientation());
            }
        }

        return $dto;
    }
}
