<?php


namespace Wideti\DomainBundle\Gateways\Consents;


class Condition
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $description;

    /**
     * Condition constructor.
     * @param string $id
     * @param string $description
     */
    private function __construct($id, $description)
    {
        $this->id = $id;
        $this->description = $description;
    }

    /**
     * @param string $id
     * @param string $description
     * @return Condition
     */
    public static function create($id, $description) {
        return new Condition($id, $description);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
