<?php

namespace Wideti\DomainBundle\Service\SecretQuestion;

use Wideti\DomainBundle\Service\SecretQuestion\Data\Answer;
use Wideti\DomainBundle\Service\SecretQuestion\Data\AnswerValidate;

interface SecretQuestionManagerInterface
{
    public function createSecretAnswer(Answer $answer, $traceHeaders = []);
    public function getSecretQuestion($traceHeaders = []);
    public function validate(AnswerValidate $validate, $traceHeaders = []);
    public function getQuestionAnsweredInfo($guestId, $traceHeaders = []);
}