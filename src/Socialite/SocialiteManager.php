<?php

namespace Pgyf\Opensdk\Kernel\Socialite;

use Closure;

class SocialiteManager implements Contracts\FactoryInterface
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var array
     */
    protected $resolved = [];
    /**
     * @var array
     */
    protected static $customCreators = [];
    protected const PROVIDERS = [
        Providers\Alipay::NAME => Providers\Alipay::class,
        Providers\DouYin::NAME => Providers\DouYin::class,
        Providers\OpenWeWork::NAME => Providers\OpenWeWork::class,
        Providers\WeChat::NAME => Providers\WeChat::class,
        Providers\WeWork::NAME => Providers\WeWork::class,
    ];

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * @param Config $config
     * @return self
     */
    public function config(Config $config):Contracts\FactoryInterface
    {
        $this->config = $config;

        return $this;
    }

    public function create(string $name): Contracts\ProviderInterface
    {
        $name = \strtolower($name);

        if (!isset($this->resolved[$name])) {
            $this->resolved[$name] = $this->createProvider($name);
        }

        return $this->resolved[$name];
    }

    public function extend(string $name, Closure $callback): self
    {
        self::$customCreators[\strtolower($name)] = $callback;

        return $this;
    }

    public function getResolvedProviders(): array
    {
        return $this->resolved;
    }

    public function buildProvider(string $provider, array $config): Contracts\ProviderInterface
    {
        $instance = new $provider($config);

        //$instance instanceof Contracts\ProviderInterface || throw new Exceptions\InvalidArgumentException("The {$provider} must be instanceof ProviderInterface.");
        if(!($instance instanceof Contracts\ProviderInterface)){
            throw new Exceptions\InvalidArgumentException("The {$provider} must be instanceof ProviderInterface.");
        }
        return $instance;
    }

    /**
     * @throws Exceptions\InvalidArgumentException
     */
    protected function createProvider(string $name): Contracts\ProviderInterface
    {
        $config = $this->config->get($name, []);
        $provider = $config['provider'] ?? $name;

        if (isset(self::$customCreators[$provider])) {
            return $this->callCustomCreator($provider, $config);
        }

        if (!$this->isValidProvider($provider)) {
            throw new Exceptions\InvalidArgumentException("Provider [{$name}] not supported.");
        }

        return $this->buildProvider(self::PROVIDERS[$provider] ?? $provider, $config);
    }

    protected function callCustomCreator(string $name, array $config): Contracts\ProviderInterface
    {
        return self::$customCreators[$name]($config);
    }

    protected function isValidProvider(string $provider): bool
    {
        return isset(self::PROVIDERS[$provider]) || \is_subclass_of($provider, Contracts\ProviderInterface::class);
    }
}
