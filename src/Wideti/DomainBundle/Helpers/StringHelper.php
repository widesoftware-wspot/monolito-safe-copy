<?php

namespace Wideti\DomainBundle\Helpers;

class StringHelper
{
    const ACCENT_STRINGS = 'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿ';
    const NO_ACCENT_STRINGS = 'SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyy';

    public static function utf8Fix($string)
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (!preg_match('/linux/i', $_SERVER['HTTP_USER_AGENT'])) {
                return utf8_decode($string);
            }
            return $string;
        }

        return $string;
    }

    public static function getClientDomainByUrl($url)
    {
        $domain = $url;
        $url = explode(".", $url);

        if ($url[1] == "wspot" || $url[1] == "mambowifi") {
            return $url[0];
        }
        return $domain;
    }

    public static function accentToRegex($text)
    {
        $from   = str_split(utf8_decode(self::ACCENT_STRINGS));
        $to     = str_split(strtolower(self::NO_ACCENT_STRINGS));
        $text   = utf8_decode($text);
        $regex  = array();

        foreach ($to as $key => $value) {
            if (isset($regex[$value])) {
                $regex[$value] .= $from[$key];
            } else {
                $regex[$value] = $value;
            }
        }

        foreach ($regex as $rg_key => $rg) {
            $text = preg_replace("/[$rg]/", "_{$rg_key}_", $text);
        }

        foreach ($regex as $rg_key => $rg) {
            $text = preg_replace("/_{$rg_key}_/", "[$rg]", $text);
        }

        return utf8_encode($text);
    }

    /**
     * @param string $text
     * @param int $limit
     * @return string
     */
    public static function textOverflow($text, $limit)
    {
        $subStringLimit = $limit - 3;
        return strlen($text) > $limit ? trim(mb_substr($text, 0, $subStringLimit)) . "..." : $text;
    }

    public static function slugDomain($domain) {
        return str_replace(".", "-", $domain);
    }

    public static function getProtocol($url) {
        $protocol = explode("://", $url)[0];
        if (strpos($protocol, "/")) {
            return "";
        }
        return $protocol;
    }

    public static function getHost($url) {
        $url = explode("://", $url)[1];
        $url = (substr($url, -1) == "/") ? substr($url, 0, -1) : $url;
        if (strpos($url, ":")) {
            $url = explode(":", $url)[0];
        }
        return $url;
    }

    public static function getPort($url) {
        $url = explode("://", $url)[1];
        $url = (substr($url, -1) == "/") ? substr($url, 0, -1) : $url;
        if (strpos($url, ":")) {
            return explode(":", $url)[1];
        }
        return "";
    }
}
