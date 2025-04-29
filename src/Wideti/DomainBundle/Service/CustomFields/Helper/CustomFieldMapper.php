<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 23/05/17
 * Time: 14:06
 */

namespace Wideti\DomainBundle\Service\CustomFields\Helper;

use Wideti\DomainBundle\Document\CustomFields\Field;

class CustomFieldMapper
{
    /**
     * @param $arrayFields
     * @return array
     */
    public static function arrayToObjectList($arrayFields)
    {
        if (gettype($arrayFields) != "array") {
            throw new \InvalidArgumentException("Expected array parameter");
        }

        $objArrays = [];
        foreach ($arrayFields as $fieldArray) {
            $field = new Field();
            foreach ($fieldArray as $key => $value) {
                $field->__set($key, $value);
            }
            $objArrays[] = $field;
        }
        return $objArrays;
    }
}