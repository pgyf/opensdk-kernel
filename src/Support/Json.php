<?php

namespace Pgyf\Opensdk\Kernel\Support;

use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;

/**
 * json类
 */
class Json
{

    /**
     * json字符串转为数组
     */
    public static function decode($content, $assoc = true, $depth = 512, $options = 0){
        if(empty($content)){
            return [];
        }
        if(is_array($content)){
            return $content;
        }
        $arr = json_decode($content, $assoc, $depth, $options);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException('json_decode error: ' . \json_last_error_msg());
        }
        return $arr;
    }

    /**
     * 数组转为json
     */
    public static function encode($content, $options = JSON_UNESCAPED_UNICODE, $depth = 512){
        if(empty($content)){
            return '[]';
        }
        if(!is_array($content)){
            return $content;
        }
        $json = json_encode($content, $options, $depth);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException('json_decode error: ' . \json_last_error_msg());
        }
        return $json;
    }

}

