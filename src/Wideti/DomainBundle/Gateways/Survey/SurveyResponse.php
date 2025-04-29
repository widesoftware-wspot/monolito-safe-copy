<?php


namespace Wideti\DomainBundle\Gateways\Survey;

use Exception;

class SurveyResponse
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var bool
     */
    private $showSurvey;


    /**
     * @var bool
     */
    private $hasError;
    /**
     * @var Exception
     */
    private $error;

    /**
     * Condition constructor.
     * @param string $id
     */
    private function __construct($id)
    {
        $this->hasError = false;
        $this->id = $id;
    }

    /**
     * @param string $id
     * @param string $description
     * @return SurveyResponse
     */
    public static function create($id) {
        return new SurveyResponse($id);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isShowSurvey()
    {
        return $this->showSurvey;
    }


    public function withShowSurvey($showSurvey)
    {
        $this->showSurvey = $showSurvey;
        return $this;
    }
    /**
     * @param Exception $err
     * @return SurveyResponse
     */
    public function withError(Exception $err) {
        $this->hasError = true;
        $this->error = $err;
        return $this;
    }
}
