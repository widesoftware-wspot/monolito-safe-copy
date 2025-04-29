<?php

namespace Wideti\DomainBundle\Service\SearchAccessPointsAndGroups\Dto;

class AccessPointAndGroupBuilder
{
    private $id;
    private $name;
    private $type;

    /**
     * @param int $id
     * @return $this
     */
    public function withId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function withName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function withType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return AccessPointAndGroup
     */
    public function build() {
        $response = new AccessPointAndGroup();
        $response->setId($this->id);
        $response->setName($this->name);
        $response->setType($this->type);
        return $response;
    }
}
