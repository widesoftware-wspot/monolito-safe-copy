<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Data;

class Answer
{
    private $clientId;
    private $guestId;
    private $questionId;
    private $answer;

    private function __construct()
    {
    }

    public static function create($clientId, $guestId, $questionId, $answer)
    {
        $obj             = new Answer();
        $obj->clientId   = $clientId;
        $obj->guestId    = $guestId;
        $obj->questionId = $questionId;
        $obj->answer     = $answer;
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

    public function getAnswer()
    {
        return $this->answer;
    }

    public function toArray()
    {
        return [
            'spot_id' => intval($this->clientId),
            'guest_id' => intval($this->guestId),
            'question_id' => intval($this->questionId),
            'answer' => $this->answer
        ];
    }
}