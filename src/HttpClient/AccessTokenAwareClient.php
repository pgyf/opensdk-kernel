<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Closure;
use Pgyf\Opensdk\Kernel\Contracts\AccessToken as AccessTokenInterface;
use Pgyf\Opensdk\Kernel\Contracts\AccessTokenAwareHttpClient as AccessTokenAwareHttpClientInterface;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\HttpClient;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseInterface;

use function array_merge;

/**
 * Class AccessTokenAwareClient.
 *
 * @package Pgyf\Opensdk\Kernel\HttpClient
 *
 * @method HttpClientInterface withAppId(string $value = null)
 *
 */
class AccessTokenAwareClient implements AccessTokenAwareHttpClientInterface
{
    use ClientTrait;

    /**
     * @var AccessTokenInterface
     */
    protected $accessToken = null;


    public function __construct(
        HttpClientInterface $client = null,
        AccessTokenInterface $accessToken = null,
        Closure $failureJudge = null,
        $throw = true
    ) {
        $this->client = $client ?? HttpClient::create();
        $this->accessToken = $accessToken;
        $this->failureJudge = $failureJudge;
        $this->throw = $throw;
    }


    /**
     * @param AccessTokenInterface $accessToken
     * @return self
     */
    public function withAccessToken(AccessTokenInterface $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     * @throws TransportExceptionInterface
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->accessToken) {
            $atHeader = $this->accessToken->toHeader();
            if(!empty($atHeader)){
                $options['headers'] = array_merge((array) ($options['headers']?? []), $atHeader);
            }
            else{
                $options['query'] = array_merge((array) ($options['query'] ?? []), $this->accessToken->toQuery());
            }
        }
        return $this->requestBuild($method, $url, $options);
    }


    // /**
    //  * @param MockHttpClient $mockHttpClient
    //  * @return HttpClientInterface
    //  */
    // public static function createMockClient(MockHttpClient $mockHttpClient)
    // {
    //     return new self($mockHttpClient);
    // }
}
