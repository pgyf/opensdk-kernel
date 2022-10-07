<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Closure;
use Pgyf\Opensdk\Kernel\Support\Str;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseInterface;


/**
 * Class ClientTrait.
 *
 * @package Pgyf\Opensdk\Kernel\HttpClient
 *
 * @method HttpClientInterface withAppId(string $value = null)
 *
 */
trait ClientTrait
{
    use HttpClientMethods;
    use RequestWithPresets;

    /**
     * @var HttpClientInterface
     */
    protected $client = null;

    /**
     * @var Closure
     */
    protected $failureJudge = null;
    /**
     * @var bool
     */
    protected $throw = true;


    /**
     * @param  array<string, mixed>  $options
     * @throws TransportExceptionInterface
     * @return ResponseInterface
     */
    protected function requestBuild(string $method, string $url, array $options = []): ResponseInterface
    {
        $method = strtoupper($method);
        $options = RequestUtil::formatBody($this->mergeThenResetPrepends($options));

        return new Response(
            $this->client->request($method, ltrim($url, '/'), $options),
            $this->failureJudge,
            $this->throw
        );
    }


    /**
     * Undocumented function
     * @param array $options
     * @return static
     */
    public function withOptions(array $options)
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }

    
    /**
     * @param  string  $name
     * @param  array<int, mixed>  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (Str::startsWith($name, 'with')) {
            return $this->handleMagicWithCall($name, $arguments[0] ?? null);
        }

        return $this->client->$name(...$arguments);
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
