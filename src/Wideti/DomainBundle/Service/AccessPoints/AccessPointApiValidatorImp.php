<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Respect\Validation\Validator;
use Wideti\DomainBundle\Exception\EmptyFieldsToUpdateException;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Service\AccessPoints\Dto\Api\CreateAccessPointDto;
use Wideti\DomainBundle\Service\Vendor\VendorService;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Repository\TemplateRepository;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Repository\AccessPointsRepository;

class AccessPointApiValidatorImp implements AccessPointApiValidator
{
    const FIELD_CLIENT = 'client';
    const FIELD_FRIENDLY_NAME = 'friendlyName';
    const FIELD_IDENTIFIER = 'identifier';
    const FIELD_VENDOR = 'vendor';
    const FIELD_GROUP_ID = 'groupId';
    const FIELD_TEMPLATE_ID = 'templateId';
    const FIELD_STATUS = 'status';

    const ERROR_MESSAGE_FRIENDLY_NAME_REQUIRED = "Campo obrigatório, deve ter de 2 a 100 caracteres";
    const ERROR_VENDOR = "Campo obrigatório, deve possuir um valor válido, consulte a documentação";
    const ERROR_IDENTIFIER_REQUIRED = "Campo identifier é obrigatório";
    const ERROR_MAC_ADDRESS_MASK = "Para este vendor é necessário ser um Mac Address no formato: HH-HH-HH-HH-HH-HH.";
    const ERROR_IDENTIFIER_EXISTS = "Este identificador já esta cadastrado na sua base de dados";
    const ERROR_IS_NOT_INTEGER = "Este campo precisa ser um número inteiro";
    const ERROR_GROUP_NOT_EXISTS = "O grupo informado não existe";
    const ERROR_STATUS_INVALID = "Status inválido, deve ser 1 = Ativo ou 0 = Inativo";
    const ERROR_TEMPLATE_NOT_EXISTS = 'Template informado não existe';
    const ERROR_CLIENT_NOT_EXISTS = 'O Cliente informado não existe';

    /**
     * @var VendorService $vendorService
     */
    private $vendorService;
    /**
     * @var TemplateRepository $templateRepository
     */
    private $templateRepository;
    /**
     * @var AccessPointsGroupsRepository $accessPointsGroupsRepository
     */
    private $accessPointsGroupsRepository;
    /**
     * @var AccessPointsRepository $accessPointsRepository
     */
    private $accessPointsRepository;
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * AccessPointApiValidatorImp constructor.
     * @param VendorService $vendorService
     * @param TemplateRepository $templateRepository
     * @param AccessPointsGroupsRepository $accessPointsGroupsRepository
     * @param AccessPointsRepository $accessPointsRepository
     * @param ClientRepository $clientRepository
     */
    public function __construct(
        VendorService $vendorService,
        TemplateRepository $templateRepository,
        AccessPointsGroupsRepository $accessPointsGroupsRepository,
        AccessPointsRepository $accessPointsRepository,
        ClientRepository $clientRepository
    ) {
        $this->vendorService = $vendorService;
        $this->templateRepository = $templateRepository;
        $this->accessPointsGroupsRepository = $accessPointsGroupsRepository;
        $this->accessPointsRepository = $accessPointsRepository;
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @return array
     * @throws EmptyFieldsToUpdateException
     */
    public function validate(CreateAccessPointDto $accessPointDto)
    {
        $errors = [];

        switch ($accessPointDto->getAction()) {
            case $accessPointDto::ACTION_CREATE:
                return $this->validateOnCreate($accessPointDto, $errors);
                break;
            case $accessPointDto::ACTION_UPDATE:
                return $this->validateOnUpdate($accessPointDto, $errors);
                break;
            case $accessPointDto::ACTION_INTERNAL_CREATE:
                return $this->validateOnInternalCreate($accessPointDto, $errors);
                break;
        }
    }

    private function validateOnCreate(CreateAccessPointDto $accessPointDto, $errors) {
        $errors = $this->checkFriendlyName($accessPointDto, $errors);
        $errors = $this->checkStatusIsValid($accessPointDto, $errors);
        $errors = $this->checkVendor($accessPointDto, $errors);
        $errors = $this->checkIdentifierNotEmpty($accessPointDto, $errors);
        $errors = $this->checkIdentifierMacMask($accessPointDto, $errors);
        $errors = $this->checkIdentifierIsUnique($accessPointDto, $errors);
        $errors = $this->checkGroupIsValidInteger($accessPointDto, $errors);
        $errors = $this->checkGroupIdExists($accessPointDto, $errors);
        $errors = $this->checkTemplateIdExists($accessPointDto, $errors);
        return $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param $errors
     * @return array
     * @throws EmptyFieldsToUpdateException | \InvalidArgumentException
     */
    private function validateOnUpdate(CreateAccessPointDto $accessPointDto, $errors)
    {
        $this->checkIfHasNotAllowedFieldsOnUpdate($accessPointDto);
        $this->checkIfAllUpdateFieldsIsEmpty($accessPointDto);

        $errors = $this->checkIfAccessPointExistsToUpdate($accessPointDto, $errors);

        if ($accessPointDto->getFriendlyName() !== null) {
            $errors = $this->checkFriendlyName($accessPointDto, $errors);
        }

        if ($accessPointDto->getStatus() !== null) {
            $errors = $this->checkStatusIsValid($accessPointDto, $errors);
        }

        if ($accessPointDto->getGroupId() !== null) {
            $errors = $this->checkGroupIsValidInteger($accessPointDto, $errors);
            $errors = $this->checkGroupIdExists($accessPointDto, $errors);
        }

        if ($accessPointDto->getTemplateId() !== null) {
            $errors = $this->checkTemplateIdExists($accessPointDto, $errors);
        }
        return $errors;
    }

    private function validateOnInternalCreate(CreateAccessPointDto $accessPointDto, $errors) {
        $errors = $this->checkClient($accessPointDto, $errors);
        $errors = $this->checkFriendlyName($accessPointDto, $errors);
        $errors = $this->checkVendor($accessPointDto, $errors);
        $errors = $this->checkIdentifierNotEmpty($accessPointDto, $errors);
        $errors = $this->checkIdentifierMacMask($accessPointDto, $errors);
        $errors = $this->checkIdentifierIsUnique($accessPointDto, $errors);
        return $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @throws EmptyFieldsToUpdateException
     */
    private function checkIfAllUpdateFieldsIsEmpty(CreateAccessPointDto $accessPointDto)
    {
        if (!empty($accessPointDto->getFriendlyName())) return;
        if (!empty($accessPointDto->getStatus())) return;
        if (!empty($accessPointDto->getGroupId())) return;
        if (!empty($accessPointDto->getTemplateId())) return;

        throw new EmptyFieldsToUpdateException("Não existem campos para atualizar.");
    }

    private function checkIfHasNotAllowedFieldsOnUpdate(CreateAccessPointDto $accessPointDto)
    {
        if ($accessPointDto->getVendor() || $accessPointDto->getIdentifier()) {
            throw new \InvalidArgumentException('Os campos vendor e identifier não podem ser atualizados.');
        }
    }

    private function checkIfAccessPointExistsToUpdate(CreateAccessPointDto $accessPointDto, $errors = [])
    {
        $id = $accessPointDto->getId();
        $client = $accessPointDto->getClient();
        if (!$this->accessPointsRepository->exists('id', $id, $client)) {
            $errors['id'] = "Ponto de acesso id: {$id} não existe.";
        }
        return  $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkFriendlyName(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $friendlyNameValidator = Validator::attribute('friendlyName', Validator::stringType()->length(2, 100));

        if (!$friendlyNameValidator->validate($accessPointDto)) {
            $errors[self::FIELD_FRIENDLY_NAME] = self::ERROR_MESSAGE_FRIENDLY_NAME_REQUIRED;
        }

        return $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkVendor(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $vendorList = $this->vendorService->getVendorsAsList(true);
        $apVendor = strtolower($accessPointDto->getVendor());

        if ($apVendor == '' || !in_array($apVendor, $vendorList, true)) {
            $errors[self::FIELD_VENDOR] = self::ERROR_VENDOR;
        }

        return $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkIdentifierNotEmpty(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $identifierValidator = Validator::attribute('identifier', Validator::stringType()->length(2));

        if (!$identifierValidator->validate($accessPointDto)) {
            $errors[self::FIELD_IDENTIFIER] = self::ERROR_IDENTIFIER_REQUIRED;
        }

        return $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkIdentifierMacMask(CreateAccessPointDto $accessPointDto, array $errors)
    {
        if (!$this->vendorService->hasMask($accessPointDto->getVendor())) {
            return $errors;
        }

        if (!Validator::macAddress()->validate($accessPointDto->getIdentifier())) {
            $errors[self::FIELD_IDENTIFIER] = self::ERROR_MAC_ADDRESS_MASK;
        }

        return $errors;
    }

    public function checkIdentifierIsUnique(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $identifiersExists = $this->accessPointsRepository
            ->exists(self::FIELD_IDENTIFIER, $accessPointDto->getIdentifier(), $accessPointDto->getClient());

        if ($identifiersExists) {
            $errors[self::FIELD_IDENTIFIER] = self::ERROR_IDENTIFIER_EXISTS;
        }

        return $errors;
    }

    /**
     * @param CreateAccessPointDto $accessPointDto
     * @param array $errors
     * @return array
     */
    public function checkGroupIsValidInteger(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $isInteger = Validator::intType()->validate($accessPointDto->getGroupId());
        if (!$isInteger) {
            $errors[self::FIELD_GROUP_ID] = self::ERROR_IS_NOT_INTEGER;
        }
        return $errors;
    }

    public function checkGroupIdExists(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $groupExists = $this->accessPointsGroupsRepository
            ->exists($accessPointDto->getGroupId(), $accessPointDto->getClient());

        if (!$groupExists) {
            $errors[self::FIELD_GROUP_ID] = self::ERROR_GROUP_NOT_EXISTS;
        }

        return $errors;
    }

    public function checkTemplateIdExists(CreateAccessPointDto $accessPointDto, array $errors)
    {
        if ($accessPointDto->getTemplateId() === null) {
            return $errors;
        }

        $isInteger = Validator::intType()->validate($accessPointDto->getTemplateId());

        if (!$isInteger) {
            $errors[self::FIELD_TEMPLATE_ID] = self::ERROR_IS_NOT_INTEGER;
            return $errors;
        }

        $templateExists = $this->templateRepository
            ->exists($accessPointDto->getTemplateId(), $accessPointDto->getClient());

        if (!$templateExists) {
            $errors[self::FIELD_TEMPLATE_ID] = self::ERROR_TEMPLATE_NOT_EXISTS;
        }

        return $errors;
    }


    public function checkStatusIsValid(CreateAccessPointDto $accessPoint, array $errors)
    {
        $validStatus = [0, 1];

        if (!in_array($accessPoint->getStatus(), $validStatus, true)) {
            $errors[self::FIELD_STATUS] = self::ERROR_STATUS_INVALID;
        }

        return $errors;
    }

    private function checkClient(CreateAccessPointDto $accessPointDto, array $errors)
    {
        $clientId   = $accessPointDto->getClient()->getId();
        $client     = $this->clientRepository->findOneBy(['id' => $clientId]);

        if (!$client) {
            $errors[self::FIELD_CLIENT] = self::ERROR_CLIENT_NOT_EXISTS;
        }

        return $errors;
    }
}
