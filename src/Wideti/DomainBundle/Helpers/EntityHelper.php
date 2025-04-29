<?php

namespace Wideti\DomainBundle\Helpers;

class EntityHelper
{
    /**
     * @param $values
     * @param $entityClassName
     * @return array|object
     *
     * Para fazer a conversão, em uma entidade, a variavel $values deve ser uma StdClass
     * ou um array de StdClass, e o $entityClassName deve ser uma sctring com o caminho
     * absoluto da classe que deseja retornar.
     *
     */
    public static function structToEntity($values, $entityClassName)
    {
        if (is_array($values)) {
            $returnArray = [];
            foreach ($values as $obj) {
                $inArray = EntityHelper::structToArray($obj);
                $entityObject = EntityHelper::newInstanceFromString($entityClassName);

                foreach ($inArray as $key => $value) {
                    if($key == "properties" && array_key_exists('email',$value)){
                        $value['email'] = strtolower($value['email']);
                    }
                    $entityObject->$key = $value;
                }
                $returnArray[] = $entityObject;
            }

            return $returnArray;
        }

        $inArray = EntityHelper::structToArray($values);
        $entityObject = EntityHelper::newInstanceFromString($entityClassName);

        foreach ($inArray as $key => $value) {
            if($key == "properties" && array_key_exists('email',$value)){
                $value['email'] = strtolower($value['email']);
            }
            $entityObject->$key = $value;
        }

        return $entityObject;
    }

    public static function newInstanceFromString($className)
    {
        $clazz = new \ReflectionClass($className);
        return $clazz->newInstance();
    }

    public static function structToArray($struct)
    {
        return json_decode(json_encode($struct), true);
    }
}
