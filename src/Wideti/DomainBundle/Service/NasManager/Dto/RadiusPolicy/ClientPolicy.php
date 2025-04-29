<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

final class ClientPolicy implements \JsonSerializable
{
    private $id;
	private $plan;
    private $apCheck;

	/**
	 * @param integer $id
	 * @param $plan
	 * @param boolean $apCheck
	 */
    public function __construct($id, $plan, $apCheck)
    {
        $this->id = $id;
	    $this->plan = $plan;
	    $this->apCheck = $apCheck;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

	/**
	 * @return string
	 */
	public function getPlan()
	{
		return $this->plan;
	}

    /**
     * @return bool
     */
    public function isApCheck()
    {
        return $this->apCheck;
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