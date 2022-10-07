<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Closure;
use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;

use function array_reverse;
use function array_unshift;
use function call_user_func;
use function func_get_args;
use function gettype;
use function is_array;
use function is_callable;
use function is_string;
use function method_exists;
use function spl_object_hash;

trait InteractWithHandlers
{
    /**
     * @var array<int, array{hash: string, handler: callable}>
     */
    protected $handlers = [];

    /**
     * @return array<int, array{hash: string, handler: callable}>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * @param  callable|string  $handler
     * 
     * @return static
     * @throws InvalidArgumentException
     */
    public function with($handler)
    {
        return $this->withHandler($handler);
    }

    /**
     * @param  callable|string  $handler
     * 
     * @return static
     * @throws InvalidArgumentException
     */
    public function withHandler($handler)
    {
        $this->handlers[] = $this->createHandlerItem($handler);

        return $this;
    }

    /**
     * @param  callable|string  $handler
     *
     * @return array{hash: string, handler: callable}
     * @throws InvalidArgumentException
     */
    public function createHandlerItem($handler): array
    {
        return [
            'hash' => $this->getHandlerHash($handler),
            'handler' => $this->makeClosure($handler),
        ];
    }

    /**
     * @param  callable|string  $handler
     * 
     * @throws InvalidArgumentException
     */
    protected function getHandlerHash($handler): string
    {
        // return match (true) {
        //     is_string($handler) => $handler,
        //     is_array($handler) => is_string($handler[0]) ? $handler[0].'::'.$handler[1] : get_class(
        //         $handler[0]
        //     ).$handler[1],
        //     $handler instanceof Closure => spl_object_hash($handler),
        //     default => throw new InvalidArgumentException('Invalid handler: '.gettype($handler)),
        // };
        if(is_string($handler)){
            return $handler;
        }
        if(is_array($handler)){
            return is_string($handler[0]) ? $handler[0].'::'.$handler[1] : get_class($handler[0]).$handler[1];
        }
        if($handler instanceof Closure){
            return spl_object_hash($handler);
        }
        throw new InvalidArgumentException('Invalid handler: '.gettype($handler));
    }

    /**
     * @param callable|string $handler
     * @return callable
     */
    protected function makeClosure($handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (class_exists($handler) && method_exists($handler, '__invoke')) {
            /**
             * @psalm-suppress InvalidFunctionCall
             * @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/5867
             */
            return (new $handler())(...func_get_args());
        }

        throw new InvalidArgumentException(sprintf('Invalid handler: %s.', $handler));
    }
    

    /**
     * @param callable|string $handler
     * @return static
     */
    public function prepend($handler)
    {
        return $this->prependHandler($handler);
    }

    /**
     * @param callable|string $handler
     * @return static
     */
    public function prependHandler($handler)
    {
        array_unshift($this->handlers, $this->createHandlerItem($handler));

        return $this;
    }

    /**
     * @param callable|string $handler
     * @return static
     */
    public function without($handler)
    {
        return $this->withoutHandler($handler);
    }

    /**
     * @param callable|string $handler
     * @return static
     */
    public function withoutHandler($handler)
    {
        $index = $this->indexOf($handler);

        if ($index > -1) {
            unset($this->handlers[$index]);
        }

        return $this;
    }

    /**
     * @param callable|string $handler
     * @return int
     */
    public function indexOf($handler): int
    {
        foreach ($this->handlers as $index => $item) {
            if ($item['hash'] === $this->getHandlerHash($handler)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @param mixed $value
     * @param callable|string $handler
     * @return static
     */
    public function when($value, $handler)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $this);
        }

        if ($value) {
            return $this->withHandler($handler);
        }

        return $this;
    }

    /**
     * @param mixed $result
     * @param mixed $payload
     * @return mixed
     */
    public function handle($result, $payload = null)
    {
        //$next = $result = is_callable($result) ? $result : fn (mixed $p): mixed => $result;
        $next = $result = is_callable($result) ? $result : function ($p) use ($result) { return $result;};

        foreach (array_reverse($this->handlers) as $item) {
            //$next = fn (mixed $p): mixed => $item['handler']($p, $next) ?? $result($p);
            $next = function ($p) use($item, $next, $result) { return $item['handler']($p, $next) ?? $result($p);};
        }

        return $next($payload);
    }

    /**
     * @param callable|string $handler
     * @return bool
     */
    public function has($handler): bool
    {
        return $this->indexOf($handler) > -1;
    }
}
