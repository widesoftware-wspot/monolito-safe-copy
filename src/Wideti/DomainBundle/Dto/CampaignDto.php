<?php

namespace Wideti\DomainBundle\Dto;

use Wideti\DomainBundle\Entity\Campaign;

class CampaignDto
{
    const MEDIA_IMAGE = "image";
    const MEDIA_VIDEO = "video";

    private $id;
    private $status;
    private $name;
    private $startDate;
    private $endDate;
    private $bgColor;
    private $redirectUrl;

    private $hours;
    private $callToAction;

    private $preLogin;
    private $preLoginMediaType;
    private $preLoginMediaDesktop;
    private $preLoginMediaDesktop2;
    private $preLoginMediaDesktop3;

    private $preLoginMediaMobile;
    private $preLoginMediaMobile2;
    private $preLoginMediaMobile3;

	private $preLoginMp4Media;
    private $preLoginMediaTime;
    private $preLoginFullSize;
    private $preLoginOrientation;

    private $posLogin;
    private $posLoginMediaType;
    private $posLoginMediaDesktop;
    private $posLoginMediaDesktop2;
    private $posLoginMediaDesktop3;

    private $posLoginMediaMobile;
    private $posLoginMediaMobile2;
    private $posLoginMediaMobile3;


	private $posLoginMp4Media;
    private $posLoginMediaTime;
    private $posLoginFullSize;
    private $posLoginOrientation;
    private $videoSkip;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param mixed $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param mixed $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return mixed
     */
    public function getBgColor()
    {
        return $this->bgColor;
    }

    /**
     * @param mixed $bgColor
     */
    public function setBgColor($bgColor)
    {
        $this->bgColor = $bgColor;
    }

    /**
     * @return mixed
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param mixed $redirectUrl
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * @return mixed
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * @param mixed $hours
     */
    public function setHours($hours)
    {
        $this->hours = $hours;
    }

    /**
     * @return mixed
     */
    public function getCallToAction()
    {
        return $this->callToAction;
    }

    /**
     * @param mixed $callToAction
     */
    public function setCallToAction($callToAction)
    {
        $this->callToAction = $callToAction;
    }

    /**
     * @return mixed
     */
    public function getPreLogin()
    {
        return $this->preLogin;
    }

    /**
     * @param mixed $preLogin
     */
    public function setPreLogin($preLogin)
    {
        $this->preLogin = $preLogin;
    }

    /**
     * @return mixed
     */
    public function getPreLoginMediaType()
    {
        return $this->preLoginMediaType;
    }

    /**
     * @param mixed $preLoginMediaType
     */
    public function setPreLoginMediaType($preLoginMediaType)
    {
        $this->preLoginMediaType = $preLoginMediaType;
    }

    /**
     * @return mixed
     */
    public function getPreLoginMediaDesktop()
    {
        return $this->preLoginMediaDesktop;
    }

        /**
     * @return mixed
     */
    public function getPreLoginMediaDesktop2()
    {
        return $this->preLoginMediaDesktop2;
    }

        /**
     * @return mixed
     */
    public function getPreLoginMediaDesktop3()
    {
        return $this->preLoginMediaDesktop3;
    }

    /**
     * @param mixed $preLoginMediaDesktop
     */
    public function setPreLoginMediaDesktop($preLoginMediaDesktop)
    {
        $this->preLoginMediaDesktop = $preLoginMediaDesktop;
    }

        /**
     * @param mixed $preLoginMediaDesktop2
     */
    public function setPreLoginMediaDesktop2($preLoginMediaDesktop2)
    {
        $this->preLoginMediaDesktop2 = $preLoginMediaDesktop2;
    }

        /**
     * @param mixed $preLoginMediaDesktop3
     */
    public function setPreLoginMediaDesktop3($preLoginMediaDesktop3)
    {
        $this->preLoginMediaDesktop3 = $preLoginMediaDesktop3;
    }

    /**
     * @return mixed
     */
    public function getPreLoginMediaMobile()
    {
        return $this->preLoginMediaMobile;
    }

    /**
     * @return mixed
     */
    public function getPreLoginMediaMobile2()
    {
        return $this->preLoginMediaMobile2;
    }

        /**
     * @return mixed
     */
    public function getPreLoginMediaMobile3()
    {
        return $this->preLoginMediaMobile3;
    }


    /**
     * @param mixed $preLoginMediaMobile
     */
    public function setPreLoginMediaMobile($preLoginMediaMobile)
    {
        $this->preLoginMediaMobile = $preLoginMediaMobile;
    }

        /**
     * @param mixed $preLoginMediaMobile2
     */
    public function setPreLoginMediaMobile2($preLoginMediaMobile2)
    {
        $this->preLoginMediaMobile2 = $preLoginMediaMobile2;
    }

        /**
     * @param mixed $preLoginMediaMobile3
     */
    public function setPreLoginMediaMobile3($preLoginMediaMobile3)
    {
        $this->preLoginMediaMobile3 = $preLoginMediaMobile3;
    }

    /**
     * @return mixed
     */
    public function getPreLoginMediaTime()
    {
        return $this->preLoginMediaTime;
    }

    /**
     * @param mixed $preLoginMediaTime
     */
    public function setPreLoginMediaTime($preLoginMediaTime)
    {
        $this->preLoginMediaTime = $preLoginMediaTime;
    }

    /**
     * @return mixed
     */
    public function getPreLoginFullSize()
    {
        return $this->preLoginFullSize;
    }

    /**
     * @param mixed $preLoginFullSize
     */
    public function setPreLoginFullSize($preLoginFullSize)
    {
        $this->preLoginFullSize = $preLoginFullSize;
    }

    /**
     * @return mixed
     */
    public function getPreLoginOrientation()
    {
        return $this->preLoginOrientation;
    }

    /**
     * @param mixed $preLoginOrientation
     */
    public function setPreLoginOrientation($preLoginOrientation)
    {
        $this->preLoginOrientation = $preLoginOrientation;
    }

    /**
     * @return mixed
     */
    public function getPosLogin()
    {
        return $this->posLogin;
    }

    /**
     * @param mixed $posLogin
     */
    public function setPosLogin($posLogin)
    {
        $this->posLogin = $posLogin;
    }

    /**
     * @return mixed
     */
    public function getPosLoginMediaType()
    {
        return $this->posLoginMediaType;
    }

    /**
     * @param mixed $posLoginMediaType
     */
    public function setPosLoginMediaType($posLoginMediaType)
    {
        $this->posLoginMediaType = $posLoginMediaType;
    }

    /**
     * @return mixed
     */
    public function getPosLoginMediaDesktop()
    {
        return $this->posLoginMediaDesktop;
    }

        /**
     * @return mixed
     */
    public function getPosLoginMediaDesktop2()
    {
        return $this->posLoginMediaDesktop2;
    }

        /**
     * @return mixed
     */
    public function getPosLoginMediaDesktop3()
    {
        return $this->posLoginMediaDesktop3;
    }

    /**
     * @param mixed $posLoginMediaDesktop
     */
    public function setPosLoginMediaDesktop($posLoginMediaDesktop)
    {
        $this->posLoginMediaDesktop = $posLoginMediaDesktop;
    }


    /**
     * @param mixed $posLoginMediaDesktop2
     */
    public function setPosLoginMediaDesktop2($posLoginMediaDesktop2)
    {
        $this->posLoginMediaDesktop2 = $posLoginMediaDesktop2;
    }

        /**
     * @param mixed $posLoginMediaDesktop3
     */
    public function setPosLoginMediaDesktop3($posLoginMediaDesktop3)
    {
        $this->posLoginMediaDesktop3 = $posLoginMediaDesktop3;
    }



    /**
     * @return mixed
     */
    public function getPosLoginMediaMobile()
    {
        return $this->posLoginMediaMobile;
    }

        /**
     * @return mixed
     */
    public function getPosLoginMediaMobile2()
    {
        return $this->posLoginMediaMobile2;
    }

    /**
     * @return mixed
     */
    public function getPosLoginMediaMobile3()
    {
        return $this->posLoginMediaMobile3;
    }

    /**
     * @param mixed $posLoginMediaMobile
     */
    public function setPosLoginMediaMobile($posLoginMediaMobile)
    {
        $this->posLoginMediaMobile = $posLoginMediaMobile;
    }

        /**
     * @param mixed $posLoginMediaMobile
     */
    public function setPosLoginMediaMobile2($posLoginMediaMobile2)
    {
        $this->posLoginMediaMobile2 = $posLoginMediaMobile2;
    }

        /**
     * @param mixed $posLoginMediaMobile3
     */
    public function setPosLoginMediaMobile3($posLoginMediaMobile3)
    {
        $this->posLoginMediaMobile3 = $posLoginMediaMobile3;
    }

    /**
     * @return mixed
     */
    public function getPosLoginMediaTime()
    {
        return $this->posLoginMediaTime;
    }

    /**
     * @param mixed $posLoginMediaTime
     */
    public function setPosLoginMediaTime($posLoginMediaTime)
    {
        $this->posLoginMediaTime = $posLoginMediaTime;
    }

    /**
     * @return mixed
     */
    public function getPosLoginFullSize()
    {
        return $this->posLoginFullSize;
    }

    /**
     * @param mixed $posLoginFullSize
     */
    public function setPosLoginFullSize($posLoginFullSize)
    {
        $this->posLoginFullSize = $posLoginFullSize;
    }

    /**
     * @return mixed
     */
    public function getPosLoginOrientation()
    {
        return $this->posLoginOrientation;
    }

    /**
     * @param mixed $posLoginOrientation
     */
    public function setPosLoginOrientation($posLoginOrientation)
    {
        $this->posLoginOrientation = $posLoginOrientation;
    }

	/**
	 * @return mixed
	 */
	public function getPreLoginMp4Media()
	{
		return $this->preLoginMp4Media;
	}

	/**
	 * @param mixed $preLoginMp4Media
	 */
	public function setPreLoginMp4Media($preLoginMp4Media)
	{
		$this->preLoginMp4Media = $preLoginMp4Media;
	}

	/**
	 * @return mixed
	 */
	public function getPosLoginMp4Media()
	{
		return $this->posLoginMp4Media;
	}

	/**
	 * @param mixed $posLoginMp4Media
	 */
	public function setPosLoginMp4Media($posLoginMp4Media)
	{
		$this->posLoginMp4Media = $posLoginMp4Media;
	}

    /**
     * @return mixed
     */
    public function getVideoSkip()
    {
        return $this->videoSkip;
    }

    /**
     * @param mixed $videoSkip
     */
    public function setVideoSkip($videoSkip)
    {
        if ($videoSkip) {
            $this->videoSkip = $videoSkip->getSkip();
        } else {
            $this->videoSkip = 0;
        }
    }
}
