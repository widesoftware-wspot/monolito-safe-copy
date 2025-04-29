<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AdminOAuthLoginRepository")
 * @ORM\Table(name="admin_oauth_login")
 */
class AdminOAuthLogin
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="erp_id", type="integer", nullable=false)
     */
    protected $erpId;

    /**
     * @ORM\Column(name="client_id", type="string", length=255, nullable=false)
     */
    protected $clientId;

    /**
     * @ORM\Column(name="client_secret", type="string", length=255, nullable=true)
     */
    protected $clientSecret = null;

    /**
     * @ORM\Column(name="resource", type="string", length=255, nullable=false)
     */
    protected $resource;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    protected $url;

    /**
     * @ORM\Column(name="authorize_url", type="string", length=255, nullable=false)
     */
    protected $authorizeUrl;

    /**
     * @ORM\Column(name="token_url", type="string", length=255, nullable=false)
     */
    protected $tokenUrl;

    /**
     * @ORM\Column(name="label", type="string", length=25, nullable=false)
     */
    protected $label;

    /**
     * @ORM\Column(name="sso_type", type="string", length=255, options={"default": "ad"})
     */
    protected $ssoType = "ad";

    /**
     * @ORM\Column(name="field_login", type="string", length=255, nullable=false)
     */
    protected $fieldLogin;

    /**
     * @ORM\Column(name="field_name", type="string", length=255, nullable=false)
     */
    protected $fieldName;

    /**
     * @ORM\Column(name="scope", type="string", length=255, nullable=false, options={"default": "openid"}))
     */
    protected $scope;

    /**
     * @ORM\Column(name="token_type", type="string", length=255, nullable=false, options={"default": "id_token"}))
     */
    protected $tokenType = "id_token";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
	 * @ORM\Column(name="roles_identifiers", type="json_array", nullable=false)
	 */
	private $rolesIdentifiers;

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
	public function getRolesIdentifiers()
	{
		return $this->rolesIdentifiers;
	}

	/**
	 * @param mixed $rolesIdentifiers
	 */
	public function setRolesIdentifiers(array $rolesIdentifiers = [])
	{
		$this->rolesIdentifiers = $rolesIdentifiers;
	}

    /**
     * @return mixed
     */
    public function getErpId()
    {
        return $this->erpId;
    }

    /**
     * @param mixed $erpId
     */
    public function setErpId($erpId)
    {
        $this->erpId = $erpId;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param mixed $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param mixed $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getAuthorizeUrl()
    {
        return $this->authorizeUrl;
    }

    /**
     * @param mixed $authorizeUrl
     */
    public function setAuthorizeUrl($authorizeUrl)
    {
        $this->authorizeUrl = $authorizeUrl;
    }

    /**
     * @return mixed
     */
    public function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * @param mixed $tokenUrl
     */
    public function setTokenUrl($tokenUrl)
    {
        $this->tokenUrl = $tokenUrl;
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
    public function getSsoType()
    {
        return $this->ssoType;
    }

    /**
     * @param mixed $ssoType
     */
    public function setSsoType($ssoType)
    {
        $this->ssoType = $ssoType;
    }

    /**
     * @return mixed
     */
    public function getFieldLogin()
    {
        return $this->fieldLogin;
    }

    /**
     * @param mixed $fieldLogin
     */
    public function setFieldLogin($fieldLogin)
    {
        $this->fieldLogin = $fieldLogin;
    }

    /**
     * @return mixed
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param mixed $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param mixed $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return mixed
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @param mixed $tokenType
     */
    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
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
}
