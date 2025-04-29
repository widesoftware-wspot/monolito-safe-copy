<?php

namespace Wideti\ApiBundle\Helpers\Builder;

use Wideti\ApiBundle\Helpers\Dto\CampaignVideoUrlDto;

class CampaignVideoUrlBuilder
{
    public $campaignId;
    public $type;
    public $videoUrl;
    public $videoMp4Url;
    public $bucketId;

    /**
     * @return CampaignVideoUrlBuilder
     */
    public static function getBuilder()
    {
        return new CampaignVideoUrlBuilder();
    }

    /**
     * @param $campaignId
     * @return $this
     */
    public function withCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function withType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param $videoUrl
     * @return $this
     */
    public function withVideoUrl($videoUrl)
    {
        $this->videoUrl = $videoUrl;
        return $this;
    }

	/**
	 * @param $videoMp4Url
	 * @return $this
	 */
    public function withVideoUrlMp4($videoMp4Url) {
    	$this->videoMp4Url = $videoMp4Url;
    	return $this;
	}

    /**
     * @param $bucketId
     * @return $this
     */
    public function withBucketId($bucketId)
    {
        $this->bucketId = $bucketId;
        return $this;
    }

    /**
     * @return CampaignVideoUrlDto
     */
    public function build()
    {
        return new CampaignVideoUrlDto(
            $this->campaignId,
            $this->type,
            $this->videoUrl,
            $this->videoMp4Url,
            $this->bucketId
        );
    }
}
