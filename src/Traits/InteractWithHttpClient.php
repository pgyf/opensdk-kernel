<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Pgyf\Opensdk\Kernel\HttpClient\RequestUtil;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerAwareInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;

use function property_exists;

trait InteractWithHttpClient
{
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    public function getHttpClient(): HttpClientInterface
    {
        if (!$this->httpClient) {
            $this->httpClient = $this->createHttpClient();
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @return static
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        if ($this instanceof LoggerAwareInterface && $httpClient instanceof LoggerAwareInterface
            && property_exists($this, 'logger')
            && $this->logger) {
            $httpClient->setLogger($this->logger);
        }

        return $this;
    }

    protected function createHttpClient(): HttpClientInterface
    {
        return HttpClient::create(RequestUtil::formatDefaultOptions($this->getHttpClientDefaultOptions()));
    }

    /**
     * @return array<string,mixed>
     */
    protected function getHttpClientDefaultOptions(): array
    {
        return [];
    }
}
