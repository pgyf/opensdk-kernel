<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

interface Jsonable
{
    /**
     * @return string|false
     */
    public function toJson(): string;
}
