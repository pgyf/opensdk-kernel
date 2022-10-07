<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

use Psr\Http\Message\ResponseInterface;

interface Server
{
    /**
     * @return ResponseInterface
     */
    public function serve(): ResponseInterface;
}
