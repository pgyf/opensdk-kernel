<?php

namespace Pgyf\Opensdk\Kernel\Support;


use function is_string;

class Arr
{
    /**
     * @param  array<string|int, mixed>  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', (string) $key) as $segment) {
            /** @phpstan-ignore-next-line */
            if (static::exists($array, $segment)) {
                /** @phpstan-ignore-next-line */
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * @param  array<int|string, mixed>  $array
     * @param  string|int  $key
     *
     * @return bool
     */
    public static function exists(array $array, string $key)
    {
        return array_key_exists($key, $array);
    }

    /**
     * @param  array<string|int, mixed>  $array
     * @param  string|int|null  $key
     * @param  mixed  $value
     *
     * @return array<string|int, mixed>
     */
    public static function set(array &$array, string $key, $value)
    {
        if (!is_string($key)) {
            $key = (string) $key;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * @param  array<string|int, mixed>  $array
     * @param  string  $prepend
     *
     * @return array<string|int, mixed>
     */
    public static function dot(array $array, string $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * @param  array<string|int, mixed>  $array
     * @param  string|int|array<string|int, mixed>|null  $keys
     *
     * @return bool
     */
    public static function has(array $array, string $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (empty($array)) {
            return false;
        }

        if ([] === $keys) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            /** @phpstan-ignore-next-line */
            if (static::exists($array, $key)) {
                continue;
            }

            /** @phpstan-ignore-next-line */
            foreach (explode('.', (string) $key) as $segment) {
                /** @phpstan-ignore-next-line */
                if (static::exists($subKeyArray, $segment)) {
                    /** @phpstan-ignore-next-line */
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Undocumented function
     * @param array $array
     * @return bool
     */
    public static function array_is_list(array $array): bool
    {
        if (function_exists("array_is_list")) {
            return  array_is_list($array);
        }
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }


}
