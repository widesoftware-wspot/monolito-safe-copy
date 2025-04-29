<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

final class TimeLimitPolicy implements \JsonSerializable
{
    const EMAIL_CONFIRMATION = "EMAIL_CONFIRMATION";
    const ACCESS_CODE = "ACCESS_CODE";
    const BLOCK_PER_TIME = "BLOCK_PER_TIME";
    const VALIDITY_ACCESS = "VALIDITY_ACCESS";
    const BUSINESS_HOURS = "BUSINESS_HOURS";
    const NOT_INFORMED = 'NOT_INFORMED';

    private $module;
    private $time;
    private $hasTime;

    /**
     * @param string $module
     * @param integer $time
     * @param boolean $hasTime
     */
    public function __construct($module, $time, $hasTime)
    {
        $this->module = $module;
        $this->time = $time;
        $this->hasTime = $hasTime;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return bool
     */
    public function isHasTime()
    {
        return $this->hasTime;
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

