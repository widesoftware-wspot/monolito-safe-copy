<?php

namespace Wideti\DomainBundle\Helpers;

class ValidationHelper
{
    public static function containsSpecialCharacter($value)
    {
        return !(boolean)preg_match('/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ \b0123456789]+$/', $value);
    }

    public static function validateSpecialCharacterSMS($value)
    {
        return (boolean)preg_match('/^[0-9A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ \b{}._:()!?,-]+$/', $value);
    }

    public static function validateSpecialCharacterIdentify($value)
    {
        return (boolean)preg_match('/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ \b1234567890:._-]+$/', $value);
    }
}
