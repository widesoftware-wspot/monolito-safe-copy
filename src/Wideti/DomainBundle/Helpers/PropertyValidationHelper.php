<?php


namespace Wideti\DomainBundle\Helpers;


class PropertyValidationHelper
{
    public static function isEmpty($data)
    {
        return (is_null($data) || $data == "" || $data == " ");
    }
}