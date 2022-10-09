<?php

namespace Pgyf\Opensdk\Kernel\Socialite;

use ArrayAccess;
use JsonSerializable;

class Config implements ArrayAccess, JsonSerializable
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key,$default = null)
    {
        $config = $this->config;

        if (isset($config[$key])) {
            return $config[$key];
        }

        foreach (\explode('.', $key) as $segment) {
            if (!\is_array($config) || !\array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Undocumented function
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function set(string $key, $value): array
    {
        $keys = \explode('.', $key);
        $config = &$this->config;

        while (\count($keys) > 1) {
            $key = \array_shift($keys);
            if (!isset($config[$key]) || !\is_array($config[$key])) {
                $config[$key] = [];
            }
            $config = &$config[$key];
        }

        $config[\array_shift($keys)] = $value;

        return $config;
    }

    public function has(string $key): bool
    {
        return (bool) $this->get($key);
    }

    /**
     * Undocumented function
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        if(!\is_string($offset)){
            throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');
        }
        //\is_string($offset) || throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');

        return \array_key_exists($offset, $this->config);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if(!\is_string($offset)){
            throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');
        }
        //\is_string($offset) || throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');

        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if(!\is_string($offset)){
            throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');
        }
        //\is_string($offset) || throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');

        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        if(!\is_string($offset)){
            throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');
        }
        //\is_string($offset) || throw new Exceptions\InvalidArgumentException('The $offset must be type of string here.');

        $this->set($offset, null);
    }

    public function jsonSerialize(): array
    {
        return $this->config;
    }

    public function __toString(): string
    {
        return \json_encode($this, \JSON_UNESCAPED_UNICODE) ?: '';
    }
}
