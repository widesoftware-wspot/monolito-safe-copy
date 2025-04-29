<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="configurations")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ConfigurationRepository")
 */
class Configuration
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\Column(name="group_short_code", type="string", length=50, nullable=true)
	 */
	private $groupShortCode;

	/**
	 * @ORM\Column(name="group_name", type="string", length=50, nullable=true)
	 */
	private $groupName;

	/**
	 * @ORM\Column(name="`key`", type="string", length=50, nullable=true)
	 */
	private $key;

	/**
	 * @ORM\Column(name="label", type="string", length=100, nullable=true)
	 */
	private $label;

	/**
	 * @ORM\Column(name="`type`", type="string", length=50, nullable=true)
	 */
	private $type;

	/**
	 * @ORM\Column(name="params", type="json_array", nullable=true)
	 */
	private $params;

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
		return $this->params;
	}

	/**
	 * @param mixed $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}
}
