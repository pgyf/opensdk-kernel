<?php

namespace Pgyf\Opensdk\Kernel\Support;

use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;

class Pkcs7
{
    /**
     * @throws InvalidArgumentException
     * @param string $contents
     * @return string
     */
    public static function padding(string $contents, int $blockSize)
    {
        if ($blockSize > 256) {
            throw new InvalidArgumentException('$blockSize may not be more than 256');
        }
        $padding = $blockSize - (strlen($contents) % $blockSize);
        $pattern = chr($padding);

        return $contents.str_repeat($pattern, $padding);
    }

    /**
     * @param string $contents
     * @param int $blockSize
     * @return string
     */
    public static function unpadding(string $contents, int $blockSize)
    {
        $pad = ord(substr($contents, -1));
        if ($pad < 1 || $pad > $blockSize) {
            $pad = 0;
        }

        return substr($contents, 0, (strlen($contents) - $pad));
    }
}
