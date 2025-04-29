<?php

namespace Wideti\ApiBundle\Helpers\Dto;

class CampaignVideoUrlDto implements \JsonSerializable
{
    public $campaignId;
    public $type;
    public $videoUrl;
    public $videoMp4Url;
    public $bucketId;

    /**
     * CampaignVideoUrlDto constructor.
     * @param $campaignId
     * @param $type
     * @param $videoUrl
     * @param $bucketId
     */
    public function __construct($campaignId, $type, $videoUrl, $videoMp4Url, $bucketId)
    {
        $this->campaignId = $campaignId;
        $this->type = $type;
        $this->videoUrl = $videoUrl;
        $this->videoMp4Url = $videoMp4Url;
        $this->bucketId = $bucketId;
    }

    /**
     * @return mixed
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param mixed $campaignId
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getVideoUrl()
    {
        return $this->videoUrl;
    }

    /**
     * @param mixed $videoUrl
     */
    public function setVideoUrl($videoUrl)
    {
        $this->videoUrl = $videoUrl;
    }

	/**
	 * @return mixed
	 */
	public function getVideoMp4Url()
	{
		return $this->videoMp4Url;
	}

	/**
	 * @param mixed $videoMp4Url
	 */
	public function setVideoMp4Url($videoMp4Url)
	{
		$this->videoMp4Url = $videoMp4Url;
	}

    /**
     * @return mixed
     */
    public function getBucketId()
    {
        return $this->bucketId;
    }

    /**
     * @param mixed $bucketId
     */
    public function setBucketId($bucketId)
    {
        $this->bucketId = $bucketId;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'campaignId'    => $this->campaignId,
            'type'          => $this->type,
            'videoUrl'      => $this->videoUrl,
            'bucketId'      => $this->bucketId
        ];
    }
}
