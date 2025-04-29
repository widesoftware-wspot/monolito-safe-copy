<?php

namespace Wideti\DomainBundle\Service\SecretQuestion;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Wideti\DomainBundle\Service\SecretQuestion\Data\Answer;
use Wideti\DomainBundle\Service\SecretQuestion\Data\AnswerCreatedResponse;
use Wideti\DomainBundle\Service\SecretQuestion\Data\AnswerValidate;
use Wideti\DomainBundle\Service\SecretQuestion\Data\Question;
use Wideti\DomainBundle\Service\SecretQuestion\Data\QuestionAnsweredInfo;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\BadRequest;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\Fail;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\Forbidden;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\Locked;
use Wideti\DomainBundle\Service\SecretQuestion\Exceptions\NotFound;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;

class SecretQuestionManager implements SecretQuestionManagerInterface
{

    use TranslatorAware;

    private $guzzleClient;
    private $consentGatewayUrl;

    public function __construct($consentGatewayUrl)
    {
        $this->consentGatewayUrl = $consentGatewayUrl;
        $this->guzzleClient = new GuzzleClient([
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }

    public function createSecretAnswer(Answer $answer, $traceHeaders = [])
    {
        try {
            $response = $this->guzzleClient->post("{$this->consentGatewayUrl}/v1/secret-answers", [
                "body" => json_encode($answer->toArray()),
                "headers" => $traceHeaders
            ]);
            $jsonString = $response->getBody()->getContents();
            $json = json_decode($jsonString, true);
            return AnswerCreatedResponse::create(
                $json["id"],
                $json["spot_id"],
                $json["guest_id"],
                $json["question_id"],
                $json["created_at"]);
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }

    public function getSecretQuestion($traceHeaders = [])
    {
        try {
            $response = $this->guzzleClient
                ->get("{$this->consentGatewayUrl}/v1/secret-questions",
                    [
                        "headers" => $traceHeaders,
                        "query" => [
                            'language' => $this->translator->getLocale()
                        ]
                    ]
                );
            $jsonString = $response->getBody()->getContents();
            $jsonList = json_decode($jsonString, true);
            $listQuestions = [];
            foreach ($jsonList as $item){
                $listQuestions[] = Question::create($item["id"], $item["question"]);
            }
            return $listQuestions;
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }

    private function handleException(RequestException $exception)
    {
        switch ($exception->getCode()){
            case 423:
                return new Locked($exception->getMessage(), $exception->getCode());
                break;
            case 403:
                $json = json_decode($exception->getResponse()->getBody()->getContents(), true);
                return new Forbidden($exception->getMessage(), $json["retry_attempts"], $exception->getCode());
                break;
            case 404:
                return new NotFound($exception->getMessage(), $exception->getCode());
                break;
            case 400:
                return new BadRequest($exception->getMessage(), $exception->getCode());
                break;
            default:
                return new Fail($exception->getMessage(), $exception->getCode());
                break;
        }
    }

    public function validate(AnswerValidate $validate, $traceHeaders = [])
    {
        try {
            $this->guzzleClient->post("{$this->consentGatewayUrl}/v1/guests/{$validate->getGuestId()}/sa", [
                "body" => json_encode($validate->toArray()),
                "headers" => $traceHeaders
            ]);
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }

    public function getQuestionAnsweredInfo($guestId, $traceHeaders = [])
    {
        try {
            $response = $this->guzzleClient
                ->get("{$this->consentGatewayUrl}/v1/guests/{$guestId}/question",
                    [
                        "headers" => $traceHeaders,
                        "query" => [
                            'language' => $this->translator->getLocale()
                        ]
                    ]
                );
            $jsonString = $response->getBody()->getContents();
            $jsonArr = json_decode($jsonString, true);
            return QuestionAnsweredInfo::create(
                $jsonArr["guest_id"],
                $jsonArr["spot_id"],
                $jsonArr["question_id"],
                $jsonArr["question"]
            );
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }
}