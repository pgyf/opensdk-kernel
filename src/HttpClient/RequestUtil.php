<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Pgyf\Opensdk\Kernel\Support\UserAgent;
use Pgyf\Opensdk\Kernel\Support\Xml;
use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;
use Pgyf\Opensdk\Kernel\Support\Json;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Psr\Http\Message\ServerRequestInterface;

use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;

use const ARRAY_FILTER_USE_KEY;
use const JSON_FORCE_OBJECT;
use const JSON_UNESCAPED_UNICODE;

class RequestUtil
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function mergeDefaultRetryOptions(array $options): array
    {
        return \array_merge([
            'status_codes' => GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES,
            'delay' => 1000,
            'max_delay' => 0,
            'max_retries' => 3,
            'multiplier' => 2.0,
            'jitter' => 0.1,
        ], $options);
    }


    /**
     * @param  array<string, array|mixed>  $options
     *
     * @return array<string, array|mixed>
     */
    public static function formatDefaultOptions(array $options): array
    {
        $defaultOptions = \array_filter(
            $options,
            //callback: fn ($key) => array_key_exists($key, HttpClientInterface::OPTIONS_DEFAULTS),
            function($key){
                return array_key_exists($key, HttpClientInterface::OPTIONS_DEFAULTS);
            },
            ARRAY_FILTER_USE_KEY
        );

        /** @phpstan-ignore-next-line */
        if (!isset($options['headers']['User-Agent']) && !isset($options['headers']['user-agent'])) {
            /** @phpstan-ignore-next-line */
            $defaultOptions['headers']['User-Agent'] = UserAgent::create();
        }

        return $defaultOptions;
    }

    /**
     * @param array $options
     * @param string $method
     * @return array
     */
    public static function formatOptions(array $options, string $method): array
    {
        if (array_key_exists('query', $options) && is_array($options['query']) && empty($options['query'])) {
            return $options;
        }

        if (array_key_exists('body', $options)
            || array_key_exists('json', $options)
            || array_key_exists('xml', $options)
        ) {
            return $options;
        }

        $name = in_array($method, ['GET', 'HEAD', 'DELETE']) ? 'query' : 'body';

        if (($options['headers']['Content-Type'] ?? $options['headers']['content-type'] ?? null) === 'application/json') {
            $name = 'json';
        }

        foreach ($options as $key => $value) {
            if (!array_key_exists($key, HttpClientInterface::OPTIONS_DEFAULTS)) {
                $options[$name][trim($key, '"')] = $value;
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * @param  array<string, array<string,mixed>|mixed>  $options
     *
     * @return array<string, array|mixed>
     */
    public static function formatBody(array $options): array
    {
        if (isset($options['xml'])) {
            if (is_array($options['xml'])) {
                $options['xml'] = Xml::build($options['xml']);
            }

            if (!is_string($options['xml'])) {
                throw new InvalidArgumentException('The type of `xml` must be string or array.');
            }

            /** @phpstan-ignore-next-line */
            if (!isset($options['headers']['Content-Type']) && !isset($options['headers']['content-type'])) {
                /** @phpstan-ignore-next-line */
                $options['headers']['Content-Type'] = [$options['headers'][] = 'Content-Type: text/xml'];
            }

            $options['body'] = $options['xml'];
            unset($options['xml']);
        }

        if (isset($options['json'])) {
            if (is_array($options['json'])) {
                /** XXX: 微信的 JSON 是比较奇葩的，比如菜单不能把中文 encode 为 unicode */
                $options['json'] = Json::encode(
                    $options['json'],
                    empty($options['json']) ? JSON_FORCE_OBJECT : JSON_UNESCAPED_UNICODE
                );
            }

            if (!is_string($options['json'])) {
                throw new InvalidArgumentException('The type of `json` must be string or array.');
            }

            /** @phpstan-ignore-next-line */
            if (!isset($options['headers']['Content-Type']) && !isset($options['headers']['content-type'])) {
                /** @phpstan-ignore-next-line */
                $options['headers']['Content-Type'] = [$options['headers'][] = 'Content-Type: application/json'];
            }

            $options['body'] = $options['json'];
            unset($options['json']);
        }

        return $options;
    }

    public static function createDefaultServerRequest(): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        return $creator->fromGlobals();
    }
}
