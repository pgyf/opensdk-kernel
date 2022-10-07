<?php

namespace Pgyf\Opensdk\Kernel\Support;

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
        return json_decode($content, $assoc, $depth, $options);
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
        return json_encode($content, $options, $depth);
    }

}

