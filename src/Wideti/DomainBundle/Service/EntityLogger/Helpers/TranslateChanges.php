<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Helpers;

class TranslateChanges
{
    public static function yesOrNo($bool)
    {
        if ($bool == 0) {
            return 'Não';
        } else {
            return 'Sim';
        }
    }

    public static function emptyOrNot($string)
    {
        if ($string == '') {
            return "nulo";
        } else {
            return $string;
        }
    }

    public static function activeOrNot($bool)
    {
        if ($bool == 0) {
            return 'Ativo';
        } else {
            return 'Inativo';
        }
    }
}
