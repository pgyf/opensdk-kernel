<?php

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Closure;
use Pgyf\Opensdk\Kernel\Contracts\AccessToken as AccessTokenInterface;
use Pgyf\Opensdk\Kernel\Contracts\RefreshableAccessToken as RefreshableAccessTokenInterface;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AccessTokenExpiredRetryStrategy extends GenericRetryStrategy
{
    /**
     * @var AccessTokenInterface
     */
    protected $accessToken;

    /**
     * @var Closure|null
     */
    protected $decider = null;

    public function withAccessToken(AccessTokenInterface $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function decideUsing(Closure $decider): self
    {
        $this->decider = $decider;

        return $this;
    }

    public function shouldRetry(
        $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): ?bool {
        if ((bool) $responseContent && $this->decider && ($this->decider)($context, $responseContent, $exception)) {
            if ($this->accessToken instanceof RefreshableAccessTokenInterface) {
                return (bool) $this->accessToken->refresh();
            }

            return false;
        }

        return parent::shouldRetry($context, $responseContent, $exception);
    }
}
