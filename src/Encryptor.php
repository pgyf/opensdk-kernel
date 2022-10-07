<?php

namespace Pgyf\Opensdk\Kernel;

use Pgyf\Opensdk\Kernel\Exceptions\RuntimeException;
use Pgyf\Opensdk\Kernel\Support\Pkcs7;
use Pgyf\Opensdk\Kernel\Support\Str;
use Pgyf\Opensdk\Kernel\Support\Xml;
use Exception;
use Throwable;

use function base64_decode;
use function base64_encode;
use function implode;
use function openssl_decrypt;
use function openssl_encrypt;
use function pack;
use function random_bytes;
use function sha1;
use function sort;
use function strlen;
use function substr;
use function time;
use function trim;
use function unpack;

use const OPENSSL_NO_PADDING;
use const SORT_STRING;

class Encryptor
{
    public const ERROR_INVALID_SIGNATURE = -40001; // Signature verification failed
    public const ERROR_PARSE_XML = -40002; // Parse XML failed
    public const ERROR_CALC_SIGNATURE = -40003; // Calculating the signature failed
    public const ERROR_INVALID_AES_KEY = -40004; // Invalid AESKey
    public const ERROR_INVALID_APP_ID = -40005; // Check AppID failed
    public const ERROR_ENCRYPT_AES = -40006; // AES EncryptionInterface failed
    public const ERROR_DECRYPT_AES = -40007; // AES decryption failed
    public const ERROR_INVALID_XML = -40008; // Invalid XML
    public const ERROR_BASE64_ENCODE = -40009; // Base64 encoding failed
    public const ERROR_BASE64_DECODE = -40010; // Base64 decoding failed
    public const ERROR_XML_BUILD = -40011; // XML build failed
    public const ILLEGAL_BUFFER = -41003; // Illegal buffer

    /**
     * @var string
     */
    protected $appId;
    /**
     * @var string
     */
    protected  $token;
        /**
     * @var string
     */
    protected  $aesKey;
    /**
     * @var int
     */
    protected $blockSize = 32;
    /**
     * @var string
     */
    protected $receiveId;

    public function __construct(string $appId, string $token, string $aesKey, string $receiveId = null)
    {
        $this->appId = $appId;
        $this->token = $token;
        $this->receiveId = $receiveId;
        $this->aesKey = base64_decode($aesKey.'=', true) ?: '';
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $plaintext
     * @param string|null|null $nonce
     * @param int|string|null $timestamp
     * @return string|array
     * @throws RuntimeException
     * @throws Exception
     */
    public function encrypt(string $plaintext, string $nonce = null, int $timestamp = null, $xml = true)
    {
        try {
            $plaintext = Pkcs7::padding(random_bytes(16).pack('N', strlen($plaintext)).$plaintext.$this->appId, 32);
            $ciphertext = base64_encode(
                openssl_encrypt(
                    $plaintext,
                    "aes-256-cbc",
                    $this->aesKey,
                    OPENSSL_NO_PADDING,
                    substr($this->aesKey, 0, 16)
                ) ?: ''
            );
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), self::ERROR_ENCRYPT_AES);
        }

        if(empty($nonce)){
            $nonce = Str::random();
        }
        if(empty($timestamp)){
            $timestamp = time();
        }

        $response = [
            'Encrypt' => $ciphertext,
            'MsgSignature' => $this->createSignature($this->token, $timestamp, $nonce, $ciphertext),
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce,
        ];
        if($xml){
            return Xml::build($response);
        }
        return $response;
    }

    public function createSignature(...$attributes): string
    {
        sort($attributes, SORT_STRING);

        return sha1(implode($attributes));
    }

    /**
     * @param string $ciphertext
     * @param string $msgSignature
     * @param string $nonce
     * @param int|string $timestamp
     * @return string
     * @throws RuntimeException
     */
    public function decrypt(string $ciphertext, string $msgSignature, string $nonce, int $timestamp)
    {
        $signature = $this->createSignature($this->token, $timestamp, $nonce, $ciphertext);

        if ($signature !== $msgSignature) {
            throw new RuntimeException('Invalid Signature.', self::ERROR_INVALID_SIGNATURE);
        }

        $plaintext = Pkcs7::unpadding(
            openssl_decrypt(
                base64_decode($ciphertext, true) ?: '',
                "aes-256-cbc",
                $this->aesKey,
                OPENSSL_NO_PADDING,
                substr($this->aesKey, 0, 16)
            ) ?: '',
            32
        );
        $plaintext = substr($plaintext, 16);
        $contentLength = (unpack('N', substr($plaintext, 0, 4)) ?: [])[1];

        if ($this->receiveId && trim(substr($plaintext, $contentLength + 4)) !== $this->receiveId) {
            throw new RuntimeException('Invalid appId.', self::ERROR_INVALID_APP_ID);
        }

        return substr($plaintext, 4, $contentLength);
    }
}
