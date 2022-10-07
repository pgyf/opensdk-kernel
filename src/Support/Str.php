<?php

namespace Pgyf\Opensdk\Kernel\Support;

use Exception;

use function base64_encode;
use function preg_replace;
use function random_bytes;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function trim;

class Str
{
    /**
     * From https://github.com/laravel/framework/blob/9.x/src/Illuminate/Support/Str.php#L632-L644
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function random(int $length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            /** @phpstan-ignore-next-line */
            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function snakeCase(string $string)
    {
        return trim(strtolower((string) preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
    }


    /**
     * 检查字符串中是否包含某些字符串
     * @param string       $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function contains(string $haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ('' != $needle && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }


    /**
     * 字符串转小写
     *
     * @param  string $value
     * @return string
     */
    public static function lower(string $value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * 字符串转大写
     *
     * @param  string $value
     * @return string
     */
    public static function upper(string $value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * 获取字符串的长度
     *
     * @param  string $value
     * @return int
     */
    public static function length(string $value)
    {
        return mb_strlen($value);
    }

    /**
     * 截取字符串
     *
     * @param  string   $string
     * @param  int      $start
     * @param  int|null $length
     * @return string
     */
    public static function substr(string $string, int $start, int $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }



    /**
     * 检查字符串是否以某些字符串开头
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function startsWith(string $haystack, $needles)
    {
        if (function_exists('str_starts_with')) {
            return str_starts_with($haystack, $needles);
        }
        foreach ((array) $needles as $needle) {
            if ('' != $needle && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }


    /**
     * 检查字符串是否以某些字符串结尾
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function endsWith(string $haystack, $needles)
    {
        if (function_exists('str_ends_with')) {
            return str_ends_with($haystack, $needles);
        }
        foreach ((array) $needles as $needle) {
            if ((string) $needle === static::substr($haystack, -static::length($needle))) {
                return true;
            }
        }

        return false;
    }

}
