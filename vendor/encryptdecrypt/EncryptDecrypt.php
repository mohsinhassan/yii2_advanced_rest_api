<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mohsin.hassan
 * Date: 6/24/15
 * Time: 1:24 PM
 * To change this template use File | Settings | File Templates.
 */

namespace EncryptDecrypt;


class EncryptDecrypt {
    public function simple_encrypt($text,$salt)
    {
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    public function simple_decrypt($text,$salt)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

}