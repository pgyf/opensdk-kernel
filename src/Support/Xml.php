<?php

namespace Pgyf\Opensdk\Kernel\Support;

class Xml
{
    /**
     * @return array<int|string,mixed>|null
     */
    public static function parse(string $xml)
    {
        if (empty($xml)) {
            return null;
        }
        return XmlTransformer::toArray($xml);
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @param  string  $root
     * @param  string  $item
     * @param  string|array<string, mixed>  $attr
     * @param  string  $id
     *
     * @return string
     */
    public static function build(array $data,bool $headless = true, bool $indent = false, string $root = 'xml', string $item = 'item'): string {
        return XmlTransformer::toXml($data, $headless, $indent, $root, $item);
    }

}
