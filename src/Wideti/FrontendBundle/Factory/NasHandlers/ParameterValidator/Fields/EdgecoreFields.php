<?php


namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields;


class EdgecoreFields implements RequiredFields
{
    /**
     * @var array
     */
    private $rawParameters;

    public function __construct(array $rawParameters)
    {
        $this->rawParameters = $rawParameters;
    }

    public function getApMacFields()
    {
        return ['called'];
    }

    public function getGuestMacFields()
    {
        return ['mac'];
    }

    public function getNasUrlPostFields()
    {
        return ['uamip'];
    }
}
