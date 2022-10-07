<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

interface RefreshableAccessToken extends AccessToken
{
    /**
     * @return string
     */
    public function refresh(): string;
}
