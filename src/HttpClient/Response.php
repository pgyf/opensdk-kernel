<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\HttpClient;

use ArrayAccess;
use Closure;
use Pgyf\Opensdk\Kernel\Contracts\Arrayable as ArrayableInterface;
use Pgyf\Opensdk\Kernel\Contracts\Jsonable as JsonableInterface;
use Pgyf\Opensdk\Kernel\Exceptions\BadMethodCallException;
use Pgyf\Opensdk\Kernel\Exceptions\BadResponseException;
use Pgyf\Opensdk\Kernel\Support\Xml;
use Pgyf\Opensdk\Kernel\Support\Json;
use Pgyf\Opensdk\Kernel\Support\Str;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

use function array_key_exists;
use function base64_encode;
use function file_put_contents;
use function sprintf;

/**
 * @implements \ArrayAccess<array-key, mixed>
 * @see \Symfony\Contracts\HttpClient\ResponseInterface
 */
class Response implements ArrayableInterface, JsonableInterface, ArrayAccess, ResponseInterface
{
    /**
     * @var ResponseInterface
     */
    protected $response = null;

    /**
     * @var Closure
     */
    protected $failureJudge = null;

    /**
     * @var bool
     */
    protected $throw = true;

    public function __construct(
        ResponseInterface $response,
        ?Closure $failureJudge = null,
        bool $throw = true
    ) {
        $this->response = $response;
        $this->failureJudge = $failureJudge;
        $this->throw = $throw;
    }


    /**
     * @param bool $throw
     * @return static
     */
    public function throw(bool $throw = true): self
    {
        $this->throw = $throw;

        return $this;
    }

    /**
     * @return static
     */
    public function throwOnFailure(): self
    {
        return $this->throw(true);
    }

    /**
     * @return static
     */
    public function quietly(): self
    {
        return $this->throw(false);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function judgeFailureUsing(callable $callback): self
    {
        //$this->failureJudge = $callback instanceof Closure ? $callback : fn (Response $response) => $callback($response);
        $this->failureJudge = $callback instanceof Closure ? $callback : function (Response $response) use($callback){  return $callback($response); };

        return $this;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function isSuccessful(): bool
    {
        return !$this->isFailed();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function isFailed(): bool
    {
        if ($this->is('text') && $this->failureJudge) {
            return (bool) ($this->failureJudge)($this);
        }

        try {
            return 400 <= $this->getStatusCode();
        } catch (Throwable $e) {
            return true;
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function toArray(?bool $throw = null): array
    {
        if($throw === null){
            $throw = $this->throw;
        }

        if ('' === $content = $this->response->getContent($throw)) {
            throw new BadResponseException('Response body is empty.');
        }

        $contentType = $this->getHeaderLine('content-type', $throw);

        if (str_contains($contentType, 'text/xml')
            || str_contains($contentType, 'application/xml')
            || str_starts_with($content, '<xml>')) {
            try {
                return Xml::parse($content) ?? [];
            } catch (Throwable $e) {
                throw new BadResponseException('Response body is not valid xml.', 400, $e);
            }
        }

        return $this->response->toArray($throw);
    }

    /**
     * @return string|false
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function toJson(?bool $throw = null)
    {
        return Json::encode($this->toArray($throw));
    }


    // /**
    //  * {@inheritdoc}
    //  */
    // public function toStream(?bool $throw = null)
    // {
    //     if ($this->response instanceof StreamableInterface) {
    //         return $this->response->toStream($throw ?? $this->throw);
    //     }

    //     if ($throw) {
    //         throw new BadMethodCallException(sprintf('%s does\'t implements %s', \get_class($this->response), StreamableInterface::class));
    //     }

    //     return StreamWrapper::createResource(new MockResponse());
    // }


    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function toDataUrl(): string
    {
        return 'data:'.$this->getHeaderLine('content-type').';base64,'.base64_encode($this->getContent());
    }

    // public function toPsrResponse(ResponseFactoryInterface $responseFactory = null, StreamFactoryInterface $streamFactory = null): \Psr\Http\Message\ResponseInterface
    // {
    //     $streamFactory ??= $responseFactory instanceof StreamFactoryInterface ? $responseFactory : null;

    //     if (null === $responseFactory || null === $streamFactory) {
    //         if (!class_exists(Psr17Factory::class) && !class_exists(Psr17FactoryDiscovery::class)) {
    //             throw new \LogicException('You cannot use the "Symfony\Component\HttpClient\Psr18Client" as no PSR-17 factories have been provided. Try running "composer require nyholm/psr7".');
    //         }

    //         try {
    //             $psr17Factory = class_exists(Psr17Factory::class, false) ? new Psr17Factory() : null;
    //             $responseFactory ??= $psr17Factory ?? Psr17FactoryDiscovery::findResponseFactory(); /** @phpstan-ignore-line */
    //             $streamFactory ??= $psr17Factory ?? Psr17FactoryDiscovery::findStreamFactory(); /** @phpstan-ignore-line */

    //             /** @phpstan-ignore-next-line */
    //         } catch (NotFoundException $e) {
    //             throw new \LogicException('You cannot use the "Symfony\Component\HttpClient\HttplugClient" as no PSR-17 factories have been found. Try running "composer require nyholm/psr7".', 0, $e);
    //         }
    //     }

    //     $psrResponse = $responseFactory->createResponse($this->getStatusCode());

    //     foreach ($this->getHeaders(false) as $name => $values) {
    //         foreach ($values as $value) {
    //             $psrResponse = $psrResponse->withAddedHeader($name, $value);
    //         }
    //     }

    //     $body = $this->response instanceof StreamableInterface ? $this->toStream(false) : StreamWrapper::createResource($this->response);
    //     $body = $streamFactory->createStreamFromResource($body);

    //     if ($body->isSeekable()) {
    //         $body->seek(0);
    //     }

    //     return $psrResponse->withBody($body);
    // }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws BadResponseException
     */
    public function saveAs(string $filename): string
    {
        try {
            file_put_contents($filename, $this->response->getContent(true));
        } catch (Throwable $e) {
            throw new BadResponseException(sprintf(
                'Cannot save response to %s: %s',
                $filename,
                $this->response->getContent(false)
            ), $e->getCode(), $e);
        }

        return '';
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    /**
     * Undocumented function
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->toArray()[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Response is immutable.');
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Response is immutable.');
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * 
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->response->{$name}(...$arguments);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders(?bool $throw = null): array
    {
        return $this->response->getHeaders($throw ?? $this->throw);
    }

    public function getContent(?bool $throw = null): string
    {
        return $this->response->getContent($throw ?? $this->throw);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    /**
     * @param string|null $type
     * @return mixed
     */
    public function getInfo(string $type = null)
    {
        return $this->response->getInfo($type);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function __toString(): string
    {
        return $this->toJson() ?: '';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function hasHeader(string $name, ?bool $throw = null): bool
    {
        return isset($this->getHeaders($throw)[$name]);
    }

    /**
     * @return array<array-key, mixed>
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getHeader(string $name, ?bool $throw = null): array
    {
        $name = strtolower($name);
        if($throw === null){
            $throw = $this->throw;
        }
        return $this->hasHeader($name, $throw) ? $this->getHeaders($throw)[$name] : [];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getHeaderLine(string $name, ?bool $throw = null): string
    {
        $name = strtolower($name);
        if($throw === null){
            $throw = $this->throw;
        }

        return $this->hasHeader($name, $throw) ? implode(',', $this->getHeader($name, $throw)) : '';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function is(string $type): bool
    {
        $flag = false;
        $contentType = $this->getHeaderLine('content-type');
        switch (Str::lower($type)) {
            case 'json':
            case 'xml':
            case 'html':
                if(Str::contains($contentType, '/' . $type)){
                    $flag = true;
                }
                break;
            case 'image':
            case 'audio':
            case 'video':
                if(Str::contains($contentType, $type . '/')){
                    $flag = true;
                }
                break;
            case 'text':
                if(Str::contains($contentType, 'text/') || Str::contains($contentType, '/json') || Str::contains($contentType, '/xml')){
                    $flag = true;
                }
                break;
        }
        return $flag;
    }
}
