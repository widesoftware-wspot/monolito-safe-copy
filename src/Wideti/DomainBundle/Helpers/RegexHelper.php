<?php

namespace Wideti\DomainBundle\Helpers;

class RegexHelper
{
    public static function convertMaskToRegex($mask)
    {
        $char_9 = "([0-9])";
        $char_A = "([A-Z])";
        $char_a = "([a-z])";
        $char_F = "([A-Za-z0-9])";
        $char_specials = [
            '!','@','#','$','%','&','*',
            '(',')','/','+','-',',','[',']',':',
            '<','>','|','_','.','=','^','~'
        ];

        foreach ($char_specials as $char) {
            $mask = str_replace($char, "\\" . $char, $mask);
        }

        $regex = "/^" . str_replace(['9','A','a', 'F'], [$char_9, $char_A, $char_a, $char_F], $mask) . "$/";
        return $regex;
    }
}
