<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Contracts;

use Pgyf\Opensdk\Kernel\Contracts\AccessToken as AccessTokenInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;

interface AccessTokenAwareHttpClient extends HttpClientInterface
{
    /**
     * @param AccessTokenInterface $accessToken
     * @return self
     */
    public function withAccessToken(AccessTokenInterface $accessToken);
}
