<?php

namespace Pgyf\Opensdk\Kernel\Support;

use function file_exists;
use function file_get_contents;
use function str_starts_with;

class PrivateKey
{

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string|null
     */
    protected $passphrase = null;

    public function __construct(string $key, ?string $passphrase = null)
    {
        if (file_exists($key)) {
            $this->key = "file://{$key}";
        }
    }

    public function getKey(): string
    {
        if (str_starts_with($this->key, 'file://')) {
            return file_get_contents($this->key) ?: '';
        }

        return $this->key;
    }

    /**
     * @return string|null
     */
    public function getPassphrase(): ?string
    {
        return $this->passphrase;
    }

    public function __toString(): string
    {
        return $this->getKey();
    }
}
