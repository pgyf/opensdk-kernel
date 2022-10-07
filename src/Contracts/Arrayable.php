<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

interface Arrayable
{
    /**
     * @return array
     */
    public function toArray(): array;
}
