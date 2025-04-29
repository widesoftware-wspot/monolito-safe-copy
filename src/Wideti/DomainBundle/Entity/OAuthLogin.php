<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\OAuthLoginRepository")
 * @ORM\Table(name="oauth_login", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="unique_name_domain", columns={"name", "domain"}),
 *     @ORM\UniqueConstraint(name="unique_client_id_domain", columns={"client_id", "domain"})
 * })
 * @UniqueEntity(fields={"clientId", "domain"}, message="Já existe uma integração SSO com esse client ID no painel")
 * @UniqueEntity(fields={"name", "domain"}, message="Já existe uma integração SSO com esse nome no painel")
 */
class OAuthLogin
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="domain", type="string", length=50, nullable=false)
     */
    protected $domain;

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
     * @ORM\Column(name="label_en", type="string", length=255, nullable=false)
     */
    protected $labelEn;

    /**
     * @ORM\Column(name="label_es", type="string", length=255, nullable=false)
     */
    protected $labelEs;

    /**
     * @ORM\Column(name="sso_type", type="string", length=255, options={"default": ""})
     */
    protected $ssoType = "";

    /**
     * @ORM\Column(name="field_login", type="string", length=255, nullable=false)
     */
    protected $fieldLogin;

        /**
     * @ORM\Column(name="customizeGuestGroup", type="string", length=255, nullable=true)
     */
    protected $customizeGuestGroup;

    /**
     * @ORM\Column(name="scope", type="string", length=255, nullable=false, options={"default": "openid"}))
     */
    protected $scope;

    /**
     * @ORM\Column(name="token_type", type="string", length=255, nullable=false, options={"default": "id_token"}))
     */
    protected $tokenType = "id_token";

    /**
     * @ORM\Column(name="request_missing_fields", type="boolean", options={"default":0} )
     */
    protected $requestMissingFields = false;

    /**
     * @ORM\Column(name="two_factor_required", type="boolean", options={"default":0} )
     */
    protected $twoFactorRequired = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="AccessPointsGroups", inversedBy="accessPoints")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected $group;

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
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param mixed $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
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
    public function getLabelEn()
    {
        return $this->labelEn;
    }

    /**
     * @param mixed $labelEn
     */
    public function setLabelEn($labelEn)
    {
        $this->labelEn = $labelEn;
    }

    /**
     * @return mixed
     */
    public function getLabelEs()
    {
        return $this->labelEs;
    }

    /**
     * @param mixed $labelEs
     */
    public function setLabelEs($labelEs)
    {
        $this->labelEs = $labelEs;
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
    public function getCustomizeGuestGroup()
    {
        return $this->customizeGuestGroup;
    }

    /**
     * @param mixed $customizeGuestGroup
     */
    public function setCustomizeGuestGroup($customizeGuestGroup)
    {
        $this->customizeGuestGroup = $customizeGuestGroup;
    }

    /**
     * Set group
     *
     * @param \Wideti\DomainBundle\Entity\AccessPointsGroups $group
     * @return AccessPoints
     */
    public function setGroup(\Wideti\DomainBundle\Entity\AccessPointsGroups $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Wideti\DomainBundle\Entity\AccessPointsGroups
     */
    public function getGroup()
    {
        return $this->group;
    }    /**
     * @return mixed
     */
    public function getRequestMissingFields()
    {
        return $this->requestMissingFields;
    }

    /**
     * @param mixed $requestMissingFields
     */
    public function setRequestMissingFields($requestMissingFields)
    {
        $this->requestMissingFields = $requestMissingFields;
    }

    /**
     * @return mixed
     */
    public function getTwoFactorRequired()
    {
        return $this->twoFactorRequired;
    }

    /**
     * @param mixed $twoFactorRequired
     */
    public function setTwoFactorRequired($twoFactorRequired)
    {
        $this->twoFactorRequired = $twoFactorRequired;
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
