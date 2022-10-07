<?php

namespace Pgyf\Opensdk\Kernel\Support;

use Pgyf\Opensdk\Kernel\Contracts\Aes as AesInterface;
use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;

use function base64_decode;
use function openssl_decrypt;
use function openssl_error_string;

use const OPENSSL_RAW_DATA;

class AesCbc implements AesInterface
{
    /**
     * @return string
     * @throws \Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException
     */
    public static function encrypt(string $plaintext, string $key, string $iv = null): string
    {
        $ciphertext = \openssl_encrypt($plaintext, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, (string) $iv);

        if (false === $ciphertext) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Encrypt AES CBC error.');
        }

        return base64_encode($ciphertext);
    }

    /**
     * @return string
     * @throws \Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = null): string
    {
        $plaintext = openssl_decrypt(
            base64_decode($ciphertext),
            "aes-128-cbc",
            $key,
            OPENSSL_RAW_DATA,
            (string) $iv
        );

        if (false === $plaintext) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Decrypt AES CBC error.');
        }

        return $plaintext;
    }
}
