<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Pgyf\Opensdk\Kernel\Config;
use Pgyf\Opensdk\Kernel\Contracts\Config as ConfigInterface;
use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;

use function is_array;

trait InteractWithConfig
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param  array<string,mixed>|ConfigInterface  $config
     * @throws InvalidArgumentException
     */
    public function __construct($config)
    {
        $this->config = is_array($config) ? new Config($config) : $config;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * @param ConfigInterface $config
     * @return static
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;

        return $this;
    }
}
