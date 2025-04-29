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
use Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\CustomFieldValidatorAware;
use Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\ValidatorExecute;
use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiCreateUser;
use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiSchema;
use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiSimpleSchema;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;

class SegmentationApiValidator implements ApiValidator
{
    use GuestServiceAware;

    /**
     * @var RecursiveValidator
     */
    private $validator;
    const VALIDATE_GROUP = "api";

    public function __construct(RecursiveValidator $validator)
    {
        $this->validator = $validator;
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

    /**
     * @param array $requiredFields
     * @param array $requestFields
     * @return ApiCreateUser
     */
    private function leftFields(array $requiredFields, array $requestFields)
    {
        $schema = new ApiSimpleSchema();

        if (empty($requestFields)) {
            $schema->setFields($requiredFields);
        }

        $outRequired = [];
        foreach ($requiredFields as $field) {
            if (!in_array($field, array_keys($requestFields))) {
                $outRequired[] = $field;
            }
        }
        $schema->setFields($outRequired);

        return $schema;
    }

    public function requiredFields(Request $request, $required)
    {
        $errors = [];
        $objects = $this->getObjectsFromRequest($request);

        if (empty($objects)) {
            throw new PostRequestEmptyBodyException();
        }

        foreach ($objects as $obj) {
            $requestFields = EntityHelper::structToArray($obj);
            $leftFields = $this->leftFields($required, $requestFields);

            if ($leftFields->countFieldsLeft() > 0) {
                $leftFieldError = new ApiErrors();
                $leftFieldError->setMessage("Campos obrigatórios não estão sendo enviados");
                $leftFieldError->setStatus(400);
                $leftFieldError->setMissingFields($leftFields);
                $leftFieldError->addErrors(['missing_fields' => "Campos obrigatórios não estão sendo enviados"]);
                $errors[] = $leftFieldError;
            }
        }

        return $errors;
    }

    /**
     * @param $entity
     * @param string $locale
     * @param $action
     * @return ApiErrors
     */
    public function validate($entity, $locale, $action)
    {
        // TODO: Implement validate() method.
    }

    public function hasRequiredFields(Request $request, $locale = "pt_br", $action = "create")
    {
        // TODO: Implement hasRequiredFields() method.
    }
}
