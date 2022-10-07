<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Pgyf\Opensdk\Kernel\Support\Json;

use function array_key_exists;
use function array_merge;

trait HasAttributes
{
    /**
     * @var  array<int|string,mixed> $attributes
     */
    protected $attributes = [];

    /**
     * @param  array<int|string,mixed>  $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array<int|string,mixed>
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * @return string|false
     */
    public function toJson()
    {
        return Json::encode($this->attributes);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * @param  array<int|string,mixed>  $attributes
     * @return static
     */
    public function merge(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * @return  array<int|string,mixed>  $attributes
     */
    public function jsonSerialize()
    {
        return $this->attributes;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    public function __set(string $attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    /**
     * @param string $attribute
     * @return mixed
     */
    public function __get(string $attribute)
    {
        return $this->attributes[$attribute] ?? null;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        /** @phpstan-ignore-next-line */
        return array_key_exists($offset, $this->attributes);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}
