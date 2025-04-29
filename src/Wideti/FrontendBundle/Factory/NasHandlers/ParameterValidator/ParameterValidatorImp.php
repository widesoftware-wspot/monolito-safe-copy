<?php

namespace Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator;

use Wideti\DomainBundle\Exception\NasEmptyException;
use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\Fields;
use Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\RequiredFields;

class ParameterValidatorImp implements ParameterValidator
{
    /**
     * @var RequiredFields
     */
    private $requiredFields;
    private $requestParameters;
    private $vendorName;

    /**
     * ParameterValidator constructor.
     * @param $vendorName
     * @param array $requestParameters
     */
    public function __construct($vendorName, array $requestParameters)
    {
        $className                  = 'Wideti\FrontendBundle\Factory\NasHandlers\ParameterValidator\Fields\\' .
            preg_replace('/[^A-Za-z0-9]/', '', ucwords($vendorName)) . 'Fields';

        $clazz                      = new \ReflectionClass($className);
        $this->requiredFields       = $clazz->newInstance($requestParameters);
        $this->requestParameters    = $requestParameters;
        $this->vendorName           = $vendorName;
    }

    /**
     * @return Fields
     * @throws NasWrongParametersException
     */
    public function validate()
    {
        $requiredApFields       = $this->requiredFields->getApMacFields();
        $requiredGuestFields    = $this->requiredFields->getGuestMacFields();
        $requiredUrlNasFields   = $this->requiredFields->getNasUrlPostFields();
        $requestKeys            = array_keys($this->requestParameters);

        $apMacField     = $this->validateRequired($requiredApFields, $requestKeys, 'Access Point mac address');
        $guestMacField  = $this->validateRequired($requiredGuestFields, $requestKeys, 'Guest device mac address');
        $urlPostField   = $this->validateRequired($requiredUrlNasFields, $requestKeys, 'Nas url post');

        return new Fields($apMacField, $guestMacField, $urlPostField);
    }

    /**
     * @param $requiredFields
     * @param $requestKeys
     * @param $context
     * @return mixed
     * @throws NasEmptyException
     * @throws NasWrongParametersException
     */
    private function validateRequired($requiredFields, $requestKeys, $context)
    {
        foreach ($requiredFields as $required) {
            if (in_array($required, $requestKeys)) {
                if ($required == 'mac' && empty($this->requestParameters['mac'])) {
                    break;
                }
                return $required;
            }
        }

        $jsonRequestParameters = json_encode($this->requestParameters);

        if (!$jsonRequestParameters) {
            throw new NasEmptyException('Parameters empty in factory handle');
        }

        throw new NasWrongParametersException(
            "{$context} does not exist in request ({$this->vendorName}). Request: {$jsonRequestParameters}"
        );

    }
    
}
