<?php

namespace Wideti\DomainBundle\Helpers;

class EncryptDecryptHelper
{
    /**
     *
     * @param $value
     * @param $encryptKey
     *
     * @return string
     */
    public static function encrypt($value, $encryptKey=null) {
        if (!$encryptKey) {
            $encryptKey = "wspot-monolito-v3";
        }
        // Limitar o tamanho da string original para que, após a criptografia, não ultrapasse 64 caracteres
        $valorOriginalLimitado = substr($value, 0, 61); // Limitado a 61 caracteres para acomodar padding
        return openssl_encrypt($valorOriginalLimitado, "AES-128-ECB", $encryptKey, 0);
    }

    /**
     *
     * @param $value
     * @param $encryptKey
     *
     * @return string|false
     */
    public static function decrypt($value, $encryptKey=null) {
        if (!$encryptKey) {
            $encryptKey = "wspot-monolito-v3";
        }
        return openssl_decrypt($value, "AES-128-ECB", $encryptKey, 0);
    }
}


