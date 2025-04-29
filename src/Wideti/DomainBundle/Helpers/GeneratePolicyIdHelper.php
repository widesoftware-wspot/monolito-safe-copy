<?php

namespace Wideti\DomainBundle\Helpers;

class GeneratePolicyIdHelper
{
    public static function generate()
    {
        $id = uniqid("", true);
        $id = str_replace(".", "", $id);
        $id = substr($id, 0, 20);
        return $id;
    }
}
