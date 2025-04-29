<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Wideti\ApiBundle\Exception\PostRequestEmptyBodyException;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Exception\Api\ApiException;
use Wideti\DomainBundle\Helpers\EntityHelper;
use Wideti\DomainBundle\Helpers\RegexHelper;
use Wideti\DomainBundle\Helpers\FieldsHelper;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\CustomFieldValidatorAware;
use Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\ValidatorExecute;
use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiCreateUser;
use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiSchema;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;

/**
 * Class GuestApiValidator
 * @package Wideti\DomainBundle\Service\ApiEntityValidator
 */
class GuestApiValidator implements ApiValidator
{
    use CustomFieldsAware;
    use CustomFieldValidatorAware;
    use GuestServiceAware;
    use GroupServiceAware;

    private $accessPointsService;
    /**
     * @var RecursiveValidator
     */
    private $validator;
    const VALIDATE_GROUP = "api";

    /**
     * GuestApiValidator constructor.
     * @param RecursiveValidator $validator
     * @param AccessPointsService $accessPointsService
     */
    public function __construct(RecursiveValidator $validator, AccessPointsService $accessPointsService)
    {
        $this->validator = $validator;
        $this->accessPointsService = $accessPointsService;
    }

    /**
     * @param $entity
     * @param string $locale
     * @param string $action
     * @return ApiErrors
     */
    public function validate($entity, $locale = "pt_br", $action = ApiSchema::ACTION_CREATE)
    {
        $error = new ApiErrors();
        $error->setEntityError($entity);
        $error->setMessage("Ocorreram erros na validação");
        $error->setStatus(400);

        if ($action === ApiSchema::ACTION_CREATE && !empty($entity->getId())) {
            $error->addErrors(['id' => 'Na criação de visitantes não pode ter o campo id']);
        }

        $uniqueValidation = $this->validateUniqueFields($entity, $locale);

        if ($uniqueValidation->hasErrors()) {
            $error->addErrors($uniqueValidation->getErrors());
        }

        $errorsEntity = $this->validateEntity($entity, $action);
        $errorsFields = $this->validateCustomFields($entity);
        $errorsMask = $this->validateMaskFields($entity, $locale);
        $errorsGroup = $this->validateGuestGroup($entity);

        $errorsAccessPoint = [];

        if ($action === ApiSchema::ACTION_CREATE) {
            $errorsAccessPoint = $this->validateAccessPoint($entity);
        }

        if (count($errorsEntity) > 0) {
            $error->addErrors($errorsEntity);
        }

        if (count($errorsFields) > 0) {
            $error->addErrors($errorsFields);
        }

        if (count($errorsMask) > 0) {
            $error->addErrors($errorsMask);
        }

        if (count($errorsGroup) > 0) {
            $error->addErrors($errorsGroup);
        }

        if (count($errorsAccessPoint) > 0) {
            $error->addErrors($errorsAccessPoint);
        }

        return $error;
    }

    private function validateEntity($entity, $action = ApiSchema::ACTION_CREATE)
    {
        $validationGroup = $action == ApiSchema::ACTION_UPDATE ? ['api_update'] : ['api'];
        $errors = $this->validator->validate($entity, $validationGroup);

        $pathErrors = [];

        if ($errors->count() > 0) {
            for ($i = 0; $i < $errors->count(); $i++) {
                $actualError = $errors->get($i);
                $pathErrors[$actualError->getPropertyPath()][] = $actualError->getMessage();
            }
        }

        return $pathErrors;
    }

    private function validateCustomFields(Guest $entity)
    {
        $properties = $entity->getProperties();
        $errors = [];

        if (count($properties) > 0) {
            foreach ($properties as $fieldIdentifier => $value) {
                if ($fieldIdentifier == 'dialCodePhone' && isset($properties['phone'])) {
                    if (!FieldsHelper::isValidCountryCode($value)) {
                        $errors['dialCodePhone'][] = "DDI do campo dialCodePhone inválido";
                    }
                    continue;
                } else if ($fieldIdentifier == 'dialCodeMobile' && isset($properties['mobile'])) {
                    if (!FieldsHelper::isValidCountryCode($value)) {
                        $errors['dialCodeMobile'][] ="DDI do campo dialCodeMobile inválido";
                    }
                    continue;
                }
                $err = $this->customFieldValidator->execute($fieldIdentifier, $value);
                if (!empty($err)) {
                    $errors += $err;
                }
            }
        }

        return $errors;
    }

    private function validateGuestGroup(Guest $entity)
    {
        $errors = [];

        if ($entity->getGroup()) {
            $group = $this->groupService->getAllGroups(['name' => $entity->getGroup()]);

            if (count($group) > 0) {
                $entity->setGroup($group[0]->getShortcode());
            } else {
                $errors['group'][] = 'Grupo de Visitantes informado não é válido!';
            }
        }

        return $errors;
    }

    /**
     * @param Guest $entity
     * @return array
     */
    private function validateAccessPoint(Guest $entity)
    {
        $errors = [];

        if ($entity->getRegistrationMacAddress()) {
            $accessPoint = $this->accessPointsService->getAccessPointByIdentifier(
                $entity->getRegistrationMacAddress()
            );

            if (!$accessPoint) {
                $errors['accessPoint'][] = 'Campo "registrationMacAddress" informado não é válido!';
            }
        }

        return $errors;
    }

    /**
     * @param Request $request
     * @param string $locale
     * @param string $action
     * @return array|ApiErrors
     */
    public function hasRequiredFields(Request $request, $locale = 'pt_br', $action = ApiSchema::ACTION_CREATE)
    {
        $errors = [];
        $requiredCustomFields = $this->getRequiredCustomFields();
        $requiredFields = ["password"];
        $objects = $this->getObjectsFromRequest($request);

        if (empty($objects)) {
            throw new PostRequestEmptyBodyException();
        }

        foreach ($objects as $obj) {
            $requestFields = EntityHelper::structToArray($obj);

            if ($action == ApiSchema::ACTION_UPDATE) {
                $requiredFields = [];
                $fieldsName = array_keys($requestFields);
                if (in_array('password', $fieldsName)) {
                    throw new ApiException(400, "Campo password não pode ser enviado ao atualizar um visitante");
                }
            }

            $leftFields = $this->leftFields($requiredFields, $requiredCustomFields, $requestFields);
            if ($leftFields->countFieldsLeft() > 0) {
                $leftFieldError = new ApiErrors();
                $leftFieldError->setMessage("Campos obrigatórios estão faltando");
                $leftFieldError->setStatus(400);
                $leftFieldError->setMissingFields($leftFields);
                $leftFieldError->addErrors(['missing_fields' => "Existem campos faltando na requisição"]);
                $entity = EntityHelper::structToEntity($obj, 'Wideti\DomainBundle\Document\Guest\Guest');
                $leftFieldError->setEntityError($entity);
                $errors[] = $leftFieldError;
            }
        }

        return $errors;
    }

    private function getObjectsFromRequest(Request $request)
    {
        $returnArray = [];
        $objs = json_decode($request->getContent(), false);

        if ($objs == null) {
            return $returnArray;
        }

        if (is_array($objs)) {
            return $objs;
        }

        $returnArray[] = $objs;
        return $returnArray;
    }

    private function getRequiredCustomFields()
    {
        $fields = $this->customFieldsService->getCustomFields();
        $customFields = [];

        if (count($fields) > 0) {
            foreach ($fields as $field) {
                array_push($customFields, $field->getIdentifier());
            }
        }

        return $customFields;
    }

    /**
     * @param array $requiredFields
     * @param Field[] $requiredCustomFields
     * @param array $requestFields
     * @return ApiCreateUser
     */
    private function leftFields(array $requiredFields, array $requiredCustomFields, array $requestFields)
    {
        $schema = new ApiCreateUser();

        if (empty($requestFields)) {
            $schema->setFields($requiredFields);
            $schema->setProperties($requiredCustomFields);
        }

        $outRequired = [];
        foreach ($requiredFields as $field) {
            if (!in_array($field, array_keys($requestFields))) {
                $outRequired[] = $field;
            }
        }
        $schema->setFields($outRequired);

        if (empty($requestFields['properties'])) {
            $schema->setProperties($requiredCustomFields);
        } else {
            $outCustomRequired = [];
            foreach ($requiredCustomFields as $fieldName) {
                $field = $this->customFieldsService->getFieldByNameType($fieldName);
                if ($this->customFieldsService->isRequired($field)) {
                    if (!in_array($field->getIdentifier(), array_keys($requestFields['properties']))) {
                        $outCustomRequired[] = $field->getIdentifier();
                    }
                }
            }
            $schema->setProperties($outCustomRequired);
        }

        return $schema;
    }

    private function validateMaskFields(Guest $entity, $locale = 'pt_br')
    {
        $error = [];

        $fields = $this->customFieldsService->getCustomFields();
        foreach ($fields as $field) {
            $fieldMask = isset($field->getMask()[$locale]) ? $field->getMask()[$locale] : null;
            if (!empty($fieldMask)) {
                $regex = RegexHelper::convertMaskToRegex($fieldMask);
                if (!empty($entity->getProperties()[$field->getIdentifier()])) {
                    $entityPropertieValue = $entity->getProperties()[$field->getIdentifier()];
                    if (!preg_match($regex, $entityPropertieValue)) {
                        $error[$field->getIdentifier()][] = 'Valor não atendeu a mascara ' . $fieldMask;
                    }
                }
            }
        }
        return $error;
    }

    /**
     * @param Guest $entity
     * @param $locale
     * @return FieldsErrors
     */
    private function validateUniqueFields(Guest $entity, $locale)
    {
        $fieldsErrors = new FieldsErrors();
        $uniqueFields = $this->customFieldsService->getUniqueFields();

        foreach ($uniqueFields as $field) {
            $identifier = $field->getIdentifier();
            $value = $entity->getProperties()[$identifier];
            if ($identifier === "email"){
                $value = strtolower($value);
            }
            $guests = $this->guestService->findGuestByProperty($identifier, $value);

            foreach ($guests as $guest) {
                if ($entity->getId() != $guest->getId()) {
                    $fieldsErrors->addError($identifier, "O campo {$field->getName()[$locale]} já esta cadastrado.");
                }
            }
        }

        return $fieldsErrors;
    }

    public function requiredFields(Request $request, $required)
    {
        // TODO: Implement requiredFields() method.
    }
}
