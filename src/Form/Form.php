<?php

namespace Pgyf\Opensdk\Kernel\Form;

use Pgyf\Opensdk\Kernel\Symfony\Component\Mime\Part\DataPart;
use Pgyf\Opensdk\Kernel\Symfony\Component\Mime\Part\Multipart\FormDataPart;

class Form
{

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @param  array<string|array|DataPart>  $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param  array<string|array|DataPart>  $fields
     */
    public static function create(array $fields): Form
    {
        return new self($fields);
    }

    /**
     * @return  array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->toOptions();
    }

    /**
     * @return array<string,mixed>
     */
    public function toOptions(): array
    {
        $formData = new FormDataPart($this->fields);
        return [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToString(),
        ];
    }
}
