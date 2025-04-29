<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator;

use Symfony\Component\HttpFoundation\Request;

interface ApiValidator
{
    /**
     * @param $entity
     * @param string $locale
     * @param $action
     * @return ApiErrors
     */
    public function validate($entity, $locale, $action);
    public function hasRequiredFields(Request $request, $locale = "pt_br", $action = "create");
    public function requiredFields(Request $request, $required);
}
