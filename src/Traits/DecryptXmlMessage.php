<?php

namespace Pgyf\Opensdk\Kernel\Traits;

use Pgyf\Opensdk\Kernel\Encryptor;
use Pgyf\Opensdk\Kernel\Exceptions\BadRequestException;
use Pgyf\Opensdk\Kernel\Message;
use Pgyf\Opensdk\Kernel\Support\Xml;

trait DecryptXmlMessage
{
    /**
     * @throws \Pgyf\Opensdk\Kernel\Exceptions\RuntimeException
     * @throws BadRequestException
     */
    public function decryptMessage(
        Message $message,
        Encryptor $encryptor,
        string $signature,
        $timestamp,
        string $nonce
    ): Message {
        $ciphertext = $message->Encrypt;

        $this->validateSignature($encryptor->getToken(), $ciphertext, $signature, $timestamp, $nonce);

        $message->merge(Xml::parse(
            $encryptor->decrypt(
                $ciphertext,
                $signature,
                $nonce,
                $timestamp
            )
        ) ?? []);

        return $message;
    }

    /**
     * @throws BadRequestException
     */
    protected function validateSignature(
        string $token,
        string $ciphertext,
        string $signature,
        $timestamp,
        string $nonce
    ): void {
        if (empty($signature)) {
            throw new BadRequestException('Request signature must not be empty.');
        }

        $params = [$token, $timestamp, $nonce, $ciphertext];

        sort($params, SORT_STRING);

        if ($signature !== sha1(implode($params))) {
            throw new BadRequestException('Invalid request signature.');
        }
    }
}
