<?php
/**
 * Created by PhpStorm.
 * User: eder
 * Date: 06/06/18
 * Time: 11:40
 */

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;


class FakeFields implements RequiredFields
{
    /**
     * @var array
     */
    private $rawParameters;

    /**
     * RequiredFields constructor.
     * @param array $rawParameters
     */
    public function __construct(array $rawParameters)
    {
        $this->rawParameters = $rawParameters;
    }

    /**
     * @return array
     */
    public function getApMacFields()
    {
        return ['ap_mac'];
    }

    /**
     * @return array
     */
    public function getGuestMacFields()
    {
        return ['guest_mac'];
    }

    /**
     * @return array
     */
    public function getNasUrlPostFields()
    {
        return ['url'];
    }
}