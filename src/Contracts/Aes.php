<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

interface Aes
{
    /**
     * @param string $plaintext
     * @param string $key
     * @param string|null $iv
     * @return string
     */
    public static function encrypt(string $plaintext, string $key,string $iv = null): string;

    /**
     * @param string $ciphertext
     * @param string $key
     * @param string|null $iv
     * @return string
     */
    public static function decrypt(string $ciphertext, string $key,string $iv = null): string;

}
