<?php

namespace Wideti\DomainBundle\Helpers;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignMediaImage;

class CampaignMediaHelper
{
    public static function hasImage(Campaign $campaign)
    {
        if ($campaign->getCampaignMediaImage()->count() == 0) {
            return false;
        }
        return true;
    }

    public static function hasVideo(Campaign $campaign)
    {
        if ($campaign->getCampaignMediaVideo()->count() == 0) {
            return false;
        }
        return true;
    }

    public static function hasOnPreLogin(Campaign $campaign)
    {
        $images = $campaign->getCampaignMediaImage();

        /**
         * @var CampaignMediaImage $image
         */
        foreach ($images as $image) {
            if ($image->getStep() == Campaign::STEP_PRE_LOGIN) {
                return true;
            }
        }
        return false;
    }

    public static function hasOnPosLogin(Campaign $campaign)
    {
        $images = $campaign->getCampaignMediaImage();

        /**
         * @var CampaignMediaImage $image
         */
        foreach ($images as $image) {
            if ($image->getStep() == Campaign::STEP_POS_LOGIN) {
                return true;
            }
        }
        return false;
    }

    public static function getMedias(Campaign $campaign)
    {
        $preLoginImageDesktop   = '';
        $preLoginImageDesktop2   = '';
        $preLoginImageDesktop3   = '';


        $preLoginImageMobile    = '';
        $preLoginImageMobile2    = '';
        $preLoginImageMobile3   = '';

        $preLoginExhibitionTime = '';

        $posLoginImageDesktop   = '';
        $posLoginImageDesktop2   = '';
        $posLoginImageDesktop3   = '';

        $posLoginImageMobile    = '';
        $posLoginImageMobile2    = '';
        $posLoginImageMobile3    = '';

        $posLoginExhibitionTime = '';

        /**
         * @var CampaignMediaImage $imageMedia
         */
        foreach ($campaign->getCampaignMediaImage() as $imageMedia) {
            if ($imageMedia->getStep() == Campaign::STEP_PRE_LOGIN) {
                $preLoginImageDesktop = $imageMedia->getImageDesktop();
                $preLoginImageDesktop2 = $imageMedia->getImageDesktop2();
                $preLoginImageDesktop3 = $imageMedia->getImageDesktop3();


                $preLoginImageMobile = $imageMedia->getImageMobile();
                $preLoginImageMobile2 = $imageMedia->getImageMobile2();
                $preLoginImageMobile3 = $imageMedia->getImageMobile3();

                $preLoginExhibitionTime = $imageMedia->getExhibitionTime();
            }

            if ($imageMedia->getStep() == Campaign::STEP_POS_LOGIN) {
                $posLoginImageDesktop = $imageMedia->getImageDesktop();
                $posLoginImageDesktop2 = $imageMedia->getImageDesktop2();
                $posLoginImageDesktop3 = $imageMedia->getImageDesktop3();

                $posLoginImageMobile = $imageMedia->getImageMobile();
                $posLoginImageMobile2 = $imageMedia->getImageMobile2();
                $posLoginImageMobile3 = $imageMedia->getImageMobile3();

                $posLoginExhibitionTime = $imageMedia->getExhibitionTime();
            }
        }

        return [
            'preLoginImageDesktop'      => $preLoginImageDesktop,
            'preLoginImageDesktop2'      => $preLoginImageDesktop2,
            'preLoginImageDesktop3'      => $preLoginImageDesktop3,

            'preLoginImageMobile'       => $preLoginImageMobile,
            'preLoginImageMobile2'       => $preLoginImageMobile2,
            'preLoginImageMobile3'       => $preLoginImageMobile3,
            
            'preLoginExhibitionTime'    => $preLoginExhibitionTime,
            
            'posLoginImageDesktop'      => $posLoginImageDesktop,
            'posLoginImageDesktop2'      => $posLoginImageDesktop2,
            'posLoginImageDesktop3'      => $posLoginImageDesktop3,

            
            'posLoginImageMobile'       => $posLoginImageMobile,
            'posLoginImageMobile2'       => $posLoginImageMobile2,
            'posLoginImageMobile3'       => $posLoginImageMobile3,

            
            'posLoginExhibitionTime'    => $posLoginExhibitionTime
        ];
    }

    public static function getStepsAndMediaTypes(Campaign $campaign)
    {
        $response = [];

        if ($campaign->getCampaignMediaImage()->count() > 0) {
            foreach ($campaign->getCampaignMediaImage() as $item) {
                $key = self::translate($item->getStep());
                $response[$key] = "Sim";
                $response["{$key} Mídia"] = "Imagem";
            }
        }

        if ($campaign->getCampaignMediaVideo()->count() > 0) {
            foreach ($campaign->getCampaignMediaVideo() as $item) {
                $key = self::translate($item->getStep());
                $response[$key] = "Sim";
                $response["{$key} Mídia"] = "Vídeo";
            }
        }

        return $response;
    }

    public static function translate($word)
    {
        $trans = [
            'pre' => 'Pré Login',
            'pos' => 'Pós Login'
        ];

        return isset($trans[$word]) ? $trans[$word] : null;
    }
}
