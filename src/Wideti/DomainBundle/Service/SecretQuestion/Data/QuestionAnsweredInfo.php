<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Data;

class QuestionAnsweredInfo
{
    private $guestId;
    private $clientId;
    private $questionId;
    private $question;


    private function __construct()
    {
    }

    public static function create($guestId, $clientId, $questionId, $question)
    {
        $obj = new QuestionAnsweredInfo();
        $obj->guestId = $guestId;
        $obj->clientId = $clientId;
        $obj->questionId = $questionId;
        $obj->question = $question;
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

    public function getQuestion()
    {
        return $this->question;
    }
}