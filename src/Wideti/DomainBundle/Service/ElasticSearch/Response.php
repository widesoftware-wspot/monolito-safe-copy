<?php
namespace Wideti\DomainBundle\Service\ElasticSearch;

class Response
{
    protected $id;
    protected $type;
    protected $created;

    public function __construct(array $params)
    {
        foreach ($params as $field => $value) {
            $field = str_replace("_", "", $field);
            if (property_exists(new Response([]), $field)) {
                $this->$field = $value;
            }
        }
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
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }
}
