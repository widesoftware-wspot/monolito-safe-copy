<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

final class BandwidthPolicy implements \JsonSerializable
{
    private $download;
    private $upload;
    private $hasLimit;

    /**
     * @param integer $download
     * @param integer $upload
     * @param boolean $hasLimit
     */
    public function __construct($download, $upload, $hasLimit)
    {
        $this->download = $download;
        $this->upload = $upload;
        $this->hasLimit = $hasLimit;
    }

    /**
     * @return int
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * @return int
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * @return bool
     */
    public function isHasLimit()
    {
        return $this->hasLimit;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $params = get_object_vars($this);
        $result = [];
        foreach ($params as $param => $value) {
            $result[$param] = $value;
        }
        return $result;
    }
}
