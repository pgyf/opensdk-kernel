<?php

namespace Pgyf\Opensdk\Kernel;

use ArrayAccess;
use Pgyf\Opensdk\Kernel\Exceptions\BadRequestException;
use Pgyf\Opensdk\Kernel\Support\Xml;
use Pgyf\Opensdk\Kernel\Traits\HasAttributes;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property string $FromUserName
 * @property string $ToUserName
 * @property string $Encrypt
 * @implements ArrayAccess<array-key, mixed>
 */
abstract class Message implements ArrayAccess
{
    use HasAttributes;

    /**
     * @param  array<string,string>  $attributes
     */
    final public function __construct(array $attributes = [], ?string $originContent = '')
    {
        $this->attributes = $attributes;
    }

    /**
     * @param  ServerRequestInterface  $request
     *
     * @return Message
     * @throws BadRequestException
     */
    public static function createFromRequest(ServerRequestInterface $request)
    {
        $attributes = self::format($originContent = strval($request->getBody()));

        return new static($attributes, $originContent);
    }

    /**
     * @return array<string,string>
     * @throws BadRequestException
     */
    public static function format(string $originContent)
    {
        if (0 === stripos($originContent, '<')) {
            $attributes = Xml::parse($originContent);
        }

        // Handle JSON format.
        $dataSet = json_decode($originContent, true);

        if (JSON_ERROR_NONE === json_last_error() && $originContent) {
            $attributes = $dataSet;
        }

        if (empty($attributes) || !is_array($attributes)) {
            throw new BadRequestException('Failed to decode request contents.');
        }

        return $attributes;
    }

    /**
     * @return string
     */
    public function getOriginalContents(): string
    {
        return $this->originContent ?? '';
    }

    public function __toString()
    {
        return $this->toJson() ?: '';
    }
}
