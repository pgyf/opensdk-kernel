<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Pgyf\Opensdk\Kernel\HttpClient\AccessTokenAwareClient;

trait InteractWithClient
{
    /**
     * @var AccessTokenAwareClient
     */
    protected $client;

    public function getClient(): AccessTokenAwareClient
    {
        if (!$this->client) {
            $this->client = $this->createClient();
        }

        return $this->client;
    }

    /**
     * @param AccessTokenAwareClient $client
     * @return static
     */
    public function setClient(AccessTokenAwareClient $client)
    {
        $this->client = $client;

        return $this;
    }

    abstract public function createClient(): AccessTokenAwareClient;
}
