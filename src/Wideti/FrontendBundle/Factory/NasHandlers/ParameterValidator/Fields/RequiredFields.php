<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;

interface RequiredFields
{
    /**
     * RequiredFields constructor.
     * @param array $rawParameters
     */
    public function __construct(array $rawParameters);
    /**
     * @return array
     */
    public function getApMacFields();
    /**
     * @return array
     */
    public function getGuestMacFields();
    /**
     * @return array
     */
    public function getNasUrlPostFields();

}
