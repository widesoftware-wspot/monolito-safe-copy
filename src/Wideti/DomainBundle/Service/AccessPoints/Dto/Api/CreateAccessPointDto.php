<?php

namespace Wideti\DomainBundle\Service\AccessPoints\Dto\Api;

use Wideti\DomainBundle\Entity\Client;

class CreateAccessPointDto
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_INTERNAL_CREATE = 'internal_create';

    /** @var integer */
    private $id;
    /** @var string */
    private $friendlyName;
    /** @var string */
    private $vendor;
    /** @var string */
    private $identifier;
    /** @var string */
    private $local;
    /** @var string */
    private $timezone;
    /** @var integer */
    private $status;
    /** @var integer */
    private $templateId;
    /** @var integer */
    private $groupId;
    /** @var Client */
    private $client;

    private $action;

    /**
     * @param array $data
     * @param Client $client
     * @return CreateAccessPointDto
     * @throws \Exception
     */
    public static function createFromAssocArray($data = [], Client $client)
    {
        $ap = new CreateAccessPointDto();

        $ap->client = $client;

        if (isset($data['id'])) {
            $ap->setAction(self::ACTION_UPDATE);
        } else {
            $ap->setAction(self::ACTION_CREATE);
        }

        if (!$data) return $ap;

        if (!is_array($data)) {
            throw new \InvalidArgumentException("Argumento {$data} é inválido, consulte a documentação.");
        }

        $validFields = array_keys(get_object_vars($ap));
        $wrongFields = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $validFields, true)) {
                $wrongFields[] = $key;
            }
            $ap->$key = $value;
        }

        if (!empty($wrongFields)) {
            $fields = join(", ", $wrongFields);
            throw new \InvalidArgumentException("Campos [{$fields}] não são validos, consulte a documentação.");
        }

        return $ap;
    }

    public function getEntity()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return CreateAccessPointDto
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
    }

    /**
     * @param string $friendlyName
     * @return CreateAccessPointDto
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     * @return CreateAccessPointDto
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return CreateAccessPointDto
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @param string $local
     * @return CreateAccessPointDto
     */
    public function setLocal($local)
    {
        $this->local = $local;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return CreateAccessPointDto
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return CreateAccessPointDto
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @param int $templateId
     * @return CreateAccessPointDto
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
        return $this;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     * @return CreateAccessPointDto
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return CreateAccessPointDto
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param $action
     * @throws \Exception
     * @return CreateAccessPointDto
     */
    public function setAction($action)
    {
        if ($action != self::ACTION_CREATE && $action != self::ACTION_UPDATE && $action != self::ACTION_INTERNAL_CREATE)
        {
            throw new \Exception("O valor da action deve ser '" . self::ACTION_CREATE . "' ou '" . self::ACTION_UPDATE . "'. O valor que você tentou foi: " . $action);
        }

        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}
