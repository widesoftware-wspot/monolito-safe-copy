<?php

namespace Wideti\DomainBundle\Helpers;

class FacebookFieldsHelper
{
    public static function converter($facebookFields)
    {
        if (empty($facebookFields)) {
            return null;
        }

        $fields     = [];
        $translate  = [
            'gender'     => 'Sexo',
            'age_range'  => 'Faixa etária',
        ];

        foreach ($facebookFields as $key => $value) {
            if (array_key_exists($key, $translate)) {
                if ($key == 'age_range') {
                    if ($value >= 13 && $value <= 17) $value = '13-17';
                    if ($value >= 18 && $value <= 20) $value = '18-20';
                    if ($value >= 18) $value = '21+';
                }
                if ($key == 'gender') {
                    if ($value == 'male') $value = 'Masculino';
                    if ($value == 'female') $value = 'Feminino';
                }
                $fields[$translate[$key]] = ucfirst($value);
            }
        }

        return $fields;
    }
}
