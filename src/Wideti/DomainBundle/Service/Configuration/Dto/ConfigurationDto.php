<?php

namespace Wideti\DomainBundle\Service\Configuration\Dto;

class ConfigurationDto
{
	protected $id;
	protected $groupShortCode;
	protected $groupName;
	protected $key;
	protected $label;
	protected $type;
	protected $params;
	protected $value;

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
	public function getGroupShortCode()
	{
		return $this->groupShortCode;
	}

	/**
	 * @param mixed $groupShortCode
	 */
	public function setGroupShortCode($groupShortCode)
	{
		$this->groupShortCode = $groupShortCode;
	}

	/**
	 * @return mixed
	 */
	public function getGroupName()
	{
		return $this->groupName;
	}

	/**
	 * @param mixed $groupName
	 */
	public function setGroupName($groupName)
	{
		$this->groupName = $groupName;
	}

	/**
	 * @return mixed
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param mixed $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}

	/**
	 * @return mixed
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param mixed $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
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
	public function getParams()
	{
		$params = $this->params;
		if ($params && isset($params["choices"])) {
			$params["choices"] = array_flip($params["choices"]);
		}
		return $params;
	}

	/**
	 * @param mixed $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		if ($this->type == 'checkbox') {
			return (bool)$this->value;
		}
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
}
