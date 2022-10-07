<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

interface AccessToken
{
    public function getToken(): string;

    /**
     * @return array<string,string>
     */
    public function toQuery(): array;

    /**
     * @return array
     */
    public function toHeader(): array;

    
}