<?php

namespace Wideti\DomainBundle\Service\Configuration\Dto;

class FacebookConfigurationDto
{
    /** @var boolean */
    private $share;

    /** @var boolean */
    private $like;

    /** @var string */
    private $shareUrl;

    /** @var string */
    private $likeUrl;

    /** @var string */
    private $shareMessage;

    /** @var boolean */
    private $shareRequire;

    /** @var string */
    private $shareHashtag;

    /**
     * @return bool
     */
    public function isShare()
    {
        return $this->share;
    }

    /**
     * @param bool $share
     * @return FacebookConfigurationDto
     */
    public function setShare($share)
    {
        $this->share = $share;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLike()
    {
        return $this->like;
    }

    /**
     * @param bool $like
     * @return FacebookConfigurationDto
     */
    public function setLike($like)
    {
        $this->like = $like;
        return $this;
    }

    /**
     * @return string
     */
    public function getShareUrl()
    {
        return $this->shareUrl;
    }

    /**
     * @param string $shareUrl
     * @return FacebookConfigurationDto
     */
    public function setShareUrl($shareUrl)
    {
        $this->shareUrl = $shareUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getLikeUrl()
    {
        return $this->likeUrl;
    }

    /**
     * @param string $likeUrl
     * @return FacebookConfigurationDto
     */
    public function setLikeUrl($likeUrl)
    {
        $this->likeUrl = $likeUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getShareMessage()
    {
        return $this->shareMessage;
    }

    /**
     * @param string $shareMessage
     * @return FacebookConfigurationDto
     */
    public function setShareMessage($shareMessage)
    {
        $this->shareMessage = $shareMessage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShareRequire()
    {
        return $this->shareRequire;
    }

    /**
     * @param bool $shareRequire
     * @return FacebookConfigurationDto
     */
    public function setShareRequire($shareRequire)
    {
        $this->shareRequire = $shareRequire;
        return $this;
    }

    /**
     * @return string
     */
    public function getShareHashtag()
    {
        return $this->shareHashtag;
    }

    /**
     * @param string $shareHashtag
     * @return FacebookConfigurationDto
     */
    public function setShareHashtag($shareHashtag)
    {
        $this->shareHashtag = $shareHashtag;
        return $this;
    }


}