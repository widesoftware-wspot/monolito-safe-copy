<?php
namespace Wideti\DomainBundle\Service\NasManager\Dto\AuthReport;


class AuthEvent implements \JsonSerializable {

    /**
     * @var string
     */
    private $id;

    /**
     * @var ClientEvent
     */
    private $clientEvent;

    /**
     * @var AccessDataEvent
     */
    private $accessDataEvent;

    /**
     * @var GuestEvent
     */
    private $guestEvent;

    /**
     * @var AccessPointEvent
     */
    private $accessPointEvent;

    /**
     * @var \DateTime
     */
    private $created;

    private function __construct() {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public static function create(
        GuestEvent $guestEvent,
        AccessDataEvent $accessDataEvent,
        AccessPointEvent $accessPointEvent,
        ClientEvent $clientEvent
    ) {

        $event = new AuthEvent();
        $event->accessPointEvent = $accessPointEvent;
        $event->accessDataEvent = $accessDataEvent;
        $event->guestEvent = $guestEvent;
        $event->clientEvent = $clientEvent;
        $event->id = self::getEventId($guestEvent, $event->created);

        return $event;
    }

    private static function getEventId(GuestEvent $guest, \DateTime $created)
    {
        return $created->getTimestamp() . '-' . $guest->getId();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ClientEvent
     */
    public function getClientEvent()
    {
        return $this->clientEvent;
    }

    /**
     * @return AccessDataEvent
     */
    public function getAccessDataEvent()
    {
        return $this->accessDataEvent;
    }

    /**
     * @return GuestEvent
     */
    public function getGuestEvent()
    {
        return $this->guestEvent;
    }

    /**
     * @return AccessPointEvent
     */
    public function getAccessPointEvent()
    {
        return $this->accessPointEvent;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
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
        return [
            'id' => $this->id,
            'client' => $this->clientEvent,
            'guest' => $this->guestEvent,
            'accessPoint' => $this->accessPointEvent,
            'accessData' => $this->accessDataEvent,
            'date' => $this->created->format('c')
        ];
    }
}
