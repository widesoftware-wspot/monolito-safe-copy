<?php


namespace Wideti\DomainBundle\Service\IntegrationValidator;


use Wideti\DomainBundle\Service\ApiRDStation\Dto\IntegrationValidate;

interface IntegrationValidatorInterface
{
    /**
     * @param string $token
     * @return IntegrationValidate
     */
    public function validate($token);
}