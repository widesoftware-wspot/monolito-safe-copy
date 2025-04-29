<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\AuthReport;


use Wideti\DomainBundle\Document\Guest\Guest;

class GuestEvent implements \JsonSerializable
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $group;

    /**
     * @var string
     */
    private $registerMode;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var string
     */
    private $registrationAccessPoint;

    /**
     * @var string[]
     */
    private $properties;

    private function __construct() {}

    /**
     * @param Guest $guest
     * @return GuestEvent
     */
    public static function createFrom(Guest $guest)
    {
        $report = new GuestEvent();
        $report->id = $guest->getMysql();
        $report->group = $guest->getGroup();
        $report->properties = $guest->getProperties();
        $report->registerMode = $guest->getRegisterMode();
        $report->created = self::getCreatedDate($guest);
        $report->registrationAccessPoint = $guest->getRegistrationMacAddress();

        return $report;
    }

    /**
     * @param Guest $guest
     * @return \DateTime | null
     */
    private static function getCreatedDate(Guest $guest)
    {
        $date = $guest->getCreated();

        if ($date instanceof \MongoDate) {
            return $date->toDateTime();
        } else if ($date instanceof \DateTime) {
            return $date;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getRegisterMode()
    {
        return $this->registerMode;
    }


    /**
     * @return string[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getRegistrationAccessPoint()
    {
        return $this->registrationAccessPoint;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {

        $createdDate = $this->created
            ? $this->created->format('c')
            : null;

        return [
            'id' => $this->id,
            'created' => $createdDate,
            'registrationAccessPoint' => $this->registrationAccessPoint,
            'group' => $this->group,
            'registerMode' => $this->registerMode,
            'properties' => $this->properties,
        ];
    }
}
