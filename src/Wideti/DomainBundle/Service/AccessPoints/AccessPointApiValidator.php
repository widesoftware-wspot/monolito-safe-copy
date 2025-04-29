<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;

interface AccessPointApiValidator
{
    /**
     * @param CreateAccessPointDto $accessPointDto
     * @return array
     */
    public function validate(CreateAccessPointDto $accessPointDto);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkGroupIdExists(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkTemplateIdExists(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkFriendlyName(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkVendor(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkIdentifierNotEmpty(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkIdentifierMacMask(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkIdentifierIsUnique(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkGroupIsValidInteger(CreateAccessPointDto $accessPointDto, array $errors);

    /**
     * @param $accessPoint
     * @param $errors
     * @return array
     */
    public function checkStatusIsValid(CreateAccessPointDto $accessPoint, array $errors);
}
