<?php

namespace Wideti\DomainBundle\Helpers;

class SpecialCharactersHelper
{
    public static function checkIfHasSpecialCharacters($string)
    {
       return !(boolean)preg_match('/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ \b0123456789]+$/', $string);
    }

    public static function removeSpecialCharacters($string)
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }
}