<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Data;

class AnswerValidate
{
    private $guestId;
    private $answer;

    private function __construct()
    {
    }

    public static function create($guestId, $answer)
    {
        $obj             = new AnswerValidate();
        $obj->guestId    = $guestId;
        $obj->answer     = $answer;
        return $obj;
    }

    public function getGuestId()
    {
        return $this->guestId;
    }

    public function toArray()
    {
        return [
            'answer' => $this->answer
        ];
    }
}