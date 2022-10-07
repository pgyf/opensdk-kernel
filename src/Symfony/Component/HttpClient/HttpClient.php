<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient;

use Exception;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A factory to instantiate the best possible HTTP client for the runtime.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class HttpClient
{
    /**
     * @param array $defaultOptions     Default request's options
     * @return HttpClientInterface
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     */
    public static function create(array $defaultOptions = [])
    {
        if (!\extension_loaded('curl')) {
            throw new Exception("Curl extension is not installed");
        }
        return new CurlHttpClient($defaultOptions);
    }
}