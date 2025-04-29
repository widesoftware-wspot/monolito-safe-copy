<?php

namespace Wideti\DomainBundle\Helpers;

class LanguageHelper
{
    public static function convertLocaleToLanguage($locale)
    {
        switch ($locale) {
            case 'en':
                return 'en_US';
            case 'es':
                return 'es_ES';
            default:
                return 'pt_BR';
        }
    }
}
