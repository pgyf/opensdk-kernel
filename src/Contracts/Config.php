<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

use ArrayAccess;

/**
 * @extends ArrayAccess<string, mixed>
 */
interface Config extends ArrayAccess
{
    /**
     * @return array
     */
    public function all(): array;

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value = null): void;

    /**
     * Undocumented function
     * @param  array|string  $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);
}
