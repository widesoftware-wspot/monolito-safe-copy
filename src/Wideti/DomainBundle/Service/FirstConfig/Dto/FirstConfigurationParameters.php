<?php

namespace Wideti\DomainBundle\Service\FirstConfig\Dto;

class FirstConfigurationParameters
{
    /**
     * @var array|FieldFirstConfigDTO
     */
    private $signUpFields;

    /**
     * @var FieldFirstConfigDTO
     */
    private $signInField;

    /**
     * @return array|FieldFirstConfigDTO
     */
    public function getSignUpFields()
    {
        return $this->signUpFields;
    }

    /**
     * @param array|FieldFirstConfigDTO $signUpFields
     */
    public function setSignUpFields(array $signUpFields)
    {
        $this->signUpFields = $signUpFields;
    }

    /**
     * @return FieldFirstConfigDTO
     */
    public function getSignInField()
    {
        return $this->signInField;
    }

    /**
     * @param FieldFirstConfigDTO $signInField
     */
    public function setSignInField($signInField)
    {
        $this->signInField = $signInField;
    }
}
