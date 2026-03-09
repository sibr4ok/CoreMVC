<?php

namespace core\base\models;

use core\base\controllers\Singleton;

class Crypt
{
    use Singleton;

    private string $cryptMethod = "AES-128-CBC";
    private string $hashAlgorithm = "sha256";
    private int $hashLength = 32;

    public function encrypt(string $data): string
    {
        /* Длина вектора шифрования*/
        $iv_length = openssl_cipher_iv_length($this->cryptMethod);

        $iv = openssl_random_pseudo_bytes($iv_length);

        $cipher_text = openssl_encrypt($data, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);

        $hmac = hash_hmac($this->hashAlgorithm, $cipher_text, CRYPT_KEY, true);

        return base64_encode($iv . $hmac . $cipher_text);
    }

    public function decrypt(string $data): string|false
    {
        $crypt_data = base64_decode($data);

        $iv_length = openssl_cipher_iv_length($this->cryptMethod);

        $iv = substr($crypt_data, 0, $iv_length);
        $hmac = substr($crypt_data, $iv_length, $this->hashLength);
        $cipher_text = substr($crypt_data, $iv_length + $this->hashLength);

        $original_plaintext = openssl_decrypt($cipher_text, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);

        $calcmac = hash_hmac($this->hashAlgorithm, $cipher_text, CRYPT_KEY, true);

        if(hash_equals($hmac,$calcmac)) return $original_plaintext;

        return false;
    }

}