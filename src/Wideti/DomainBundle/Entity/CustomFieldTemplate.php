<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Wideti\DomainBundle\Document\CustomFields\Field;

/**
 * @ORM\Table(name="custom_fields_template", uniqueConstraints={@ORM\UniqueConstraint(name="unique_identifier_visibleforclients", columns={"identifier"})})
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CustomFieldsTemplateRepository")
 */
class CustomFieldTemplate implements \JsonSerializable
{
	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="identifier", type="string", length=255)
	 */
	private $identifier;

	/**
	 * @ORM\Column(name="name", type="json_array")
	 */
	private $name;

	/**
	 * @ORM\Column(name="type", type="string", length=50)
	 */
	private $type;

	/**
	 * @ORM\Column(name="choices", type="json_array", nullable=true)
	 */
	private $choices;

	/**
	 * @ORM\Column(name="validations", type="json_array", nullable=true)
	 */
	private $validations;

	/**
	 * @ORM\Column(name="mask", type="json_array", nullable=true)
	 */
	private $mask;

	/**
	 * @ORM\Column(name="is_unique", type="boolean", options={"default"=0})
	 */
	private $isUnique = 0;

	/**
	 * @ORM\Column(name="is_login", type="boolean", options={"default"=0})
	 */
	private $isLogin = 0;

	/**
	 * @ORM\Column(name="visible_for_clients", type="json_array", nullable=true)
	 */
	private $visibleForClients;

		/**
	 * @ORM\Column(name="groupId", type="json_array", nullable=true)
	 */
	private $groupId;

	public function __get($param)
	{
		return $this->$param;
	}

	public function __set($param, $value)
	{
		$this->$param = $value;
	}

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
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @param mixed $identifier
	 */
	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getNameByLocale($locale)
	{
		if ($locale == null) {
			return $this->name["pt_br"];
		}
		return $this->name[$locale];
	}

	/**
	 * @param mixed $name
	 */
	public function setNames(array $name = [])
	{
		$this->name = $name;
	}

	public function addName($language, $name)
	{
		$this->name[$language] = $name;
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
	public function getChoices()
	{
		return $this->choices;
	}

	/**
	 * @param mixed $choices
	 */
	public function setChoices(array $choices = [])
	{
		$this->choices = $choices;
	}

	/**
	 * @return mixed
	 */
	public function getValidations()
	{
		return $this->validations;
	}

	/**
	 * @param mixed $validations
	 */
	public function setValidations(array $validations = [])
	{
		$this->validations = $validations;
	}

	/**
	 * @return mixed
	 */
	public function getMask()
	{
		return $this->mask;
	}

	/**
	 * @param mixed $mask
	 */
	public function setMask(array $mask = [])
	{
		$this->mask = $mask;
	}

	/**
	 * @return mixed
	 */
	public function getIsUnique()
	{
		return $this->isUnique;
	}

	/**
	 * @param mixed $isUnique
	 */
	public function setIsUnique($isUnique)
	{
		$this->isUnique = $isUnique;
	}

	/**
	 * @return mixed
	 */
	public function getIsLogin()
	{
		return $this->isLogin;
	}

	/**
	 * @param mixed $isLogin
	 */
	public function setIsLogin($isLogin)
	{
		$this->isLogin = $isLogin;
	}

	/**
	 * @return mixed
	 */
	public function getVisibleForClients()
	{
		return $this->visibleForClients;
	}

	/**
	 * @param mixed $visibleForClients
	 */
	public function setVisibleForClients(array $visibleForClients = [])
	{
		$this->visibleForClients = $visibleForClients;
	}

		/**
	 * @return mixed
	 */
	public function getgroupId()
	{
		return $this->groupId;
	}

	/**
	 * @param mixed $groupId
	 */
	public function setGroupId(array $groupId = [])
	{
		$this->groupId = $groupId;
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
		return get_object_vars($this);
	}

	/**
	 * @return Field
	 */
	public function getField() {
		$attributes = get_object_vars($this);
		unset($attributes['id']);
		$field = new Field();
		foreach ($attributes as $key => $value) {
			$field->__set($key, $value);
		}
		return $field;
	}
}
