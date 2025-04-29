<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Data;

class AnswerCreatedResponse
{
    private $id;
    private $clientId;
    private $guestId;
    private $questionId;
    private $createdAt;

    private function __construct()
    {
    }

    public static function create($id, $clientId, $guestId, $questionId, $createdAt)
    {
        $obj             = new AnswerCreatedResponse();
        $obj->id         = $id;
        $obj->clientId   = $clientId;
        $obj->guestId    = $guestId;
        $obj->questionId = $questionId;
        $obj->createdAt  = $createdAt;
        return $obj;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getGuestId()
    {
        return $this->guestId;
    }

    public function getQuestionId()
    {
        return $this->questionId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getId()
    {
        return $this->id;
    }
}