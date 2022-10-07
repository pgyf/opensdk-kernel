<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Psr\SimpleCache\CacheInterface;

trait InteractWithCache
{
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var int
     */
    protected $cacheLifetime = 1500;

    /**
     * @var string
     */
    protected $cacheNamespace = 'pgyfopensdk';

    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(int $cacheLifetime): void
    {
        $this->cacheLifetime = $cacheLifetime;
    }

    public function getCacheNamespace(): string
    {
        return $this->cacheNamespace;
    }

    public function setCacheNamespace(string $cacheNamespace): void
    {
        $this->cacheNamespace = $cacheNamespace;
    }

    /**
     * @param CacheInterface $cache
     * @return static
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
