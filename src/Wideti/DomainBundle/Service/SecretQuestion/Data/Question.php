<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Data;

class Question
{
    private $id;
    private $question;

    private function __construct()
    {
    }

    public static function create($id, $question)
    {
        $obj = new Question();
        $obj->id = $id;
        $obj->question = $question;
        return $obj;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getQuestion()
    {
        return $this->question;
    }
}