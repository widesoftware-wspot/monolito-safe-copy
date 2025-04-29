<?php

namespace Wideti\DomainBundle\Helpers\CustomField;

use Wideti\DomainBundle\Document\CustomFields\Field;

class CustomFieldHelper
{
    /**
     * CustomFieldHelper constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param array $fieldArray
     * @return Field
     */
    public static function convertMysqlToCustomField($fieldArray = [])
    {
        $field = new Field();
        foreach ($fieldArray as $key => $value) {
            $field->__set($key, $value);
        }
        return $field;
    }

    public static function generateValueByIdentifier($identifier)
    {
        $identifier = str_replace(' ', '', ucwords(str_replace('_', ' ', $identifier)));
        $className = "Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker\\{$identifier}Faker";

        if (!class_exists($className)) {
            $className = "Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker\DefaultFaker";
        }

        $clazz = new \ReflectionClass($className);
        $faker = $clazz->newInstance();
        return $faker->generate();
    }
}
