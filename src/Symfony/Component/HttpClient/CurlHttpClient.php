<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient;

use Pgyf\Opensdk\Kernel\Support\Str;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Exception\TransportException;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\HttpClientInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * A performant implementation of the HttpClientInterface contracts based on the curl extension.
 *
 * This provides fully concurrent HTTP requests, with transparent
 * HTTP/2 push when a curl version that supports it is installed.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class CurlHttpClient implements HttpClientInterface
{


    use HttpClientTrait;

    /**
     * @var array
     */
    private $defaultOptions = self::OPTIONS_DEFAULTS + [
        'auth_ntlm' => null, // array|string - an array containing the username as first value, and optionally the
                             //   password as the second one; or string like username:password - enabling NTLM auth
        'extra' => [
            'curl' => [],    // A list of extra curl options indexed by their corresponding CURLOPT_*
        ],
        'verify_host' => false,
    ];

    private static $emptyDefaults = self::OPTIONS_DEFAULTS + ['auth_ntlm' => null];



    /**
     * @param array $defaultOptions     Default request's options
     * @param int   $maxHostConnections The maximum number of connections to a single host
     * @param int   $maxPendingPushes   The maximum number of pushed responses to accept in the queue
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     */
    public function __construct(array $defaultOptions = [])
    {
        if (!\extension_loaded('curl')) {
            throw new \LogicException('the "curl" extension is not installed.');
        }

        $this->defaultOptions['buffer'] = $this->defaultOptions['buffer'] ?? \Closure::fromCallable([__CLASS__, 'shouldBuffer']);

        if ($defaultOptions) {
            [, $this->defaultOptions] = self::prepareRequest(null, null, $defaultOptions, $this->defaultOptions);
        }
    }


    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        [$url, $options] = self::prepareRequest($method, $url, $options, $this->defaultOptions, true);
        //$scheme = $url['scheme'];
        $authority = $url['authority'];
        //$host = parse_url($authority, \PHP_URL_HOST);
        $proxy = $options['proxy']
            ?? ('https:' === $url['scheme'] ? $_SERVER['https_proxy'] ?? $_SERVER['HTTPS_PROXY'] ?? null : null)
            // Ignore HTTP_PROXY except on the CLI to work around httpoxy set of vulnerabilities
            ?? $_SERVER['http_proxy'] ?? (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? $_SERVER['HTTP_PROXY'] ?? null : null) ?? $_SERVER['all_proxy'] ?? $_SERVER['ALL_PROXY'] ?? null;
        $url = implode('', $url);

        if (!isset($options['normalized_headers']['user-agent'])) {
            $options['headers'][] = 'User-Agent: Symfony HttpClient/Curl';
        }


        // $options['max_redirects'] = $options['max_redirects'] ?? 0;
        // $options['verify_host'] = $options['verify_host'] ?? false;
        // $options['body'] = $options['body'] ?? '';
        // $options['peer_fingerprint'] = $options['peer_fingerprint'] ?? '';
        // $options['bindto'] = $options['bindto'] ?? '';
        // $options['max_duration'] = $options['max_duration'] ?? 0;

        $curlopts = [
            \CURLOPT_URL            => $url,
            \CURLOPT_TCP_NODELAY    => true,
            \CURLOPT_PROTOCOLS      => \CURLPROTO_HTTP | \CURLPROTO_HTTPS,
            \CURLOPT_REDIR_PROTOCOLS => \CURLPROTO_HTTP | \CURLPROTO_HTTPS,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLINFO_HEADER_OUT => true,
            //\CURLOPT_HEADER => true,
            \CURLOPT_RETURNTRANSFER => true, //文件流返回
            \CURLOPT_AUTOREFERER => true,
            \CURLOPT_MAXREDIRS => 0 < $options['max_redirects'] ? $options['max_redirects'] : 0,
            \CURLOPT_COOKIEFILE => '', // Keep track of cookies during redirects
            \CURLOPT_TIMEOUT => 0,
            \CURLOPT_PROXY => $proxy,
            \CURLOPT_NOPROXY => $options['no_proxy'] ?? $_SERVER['no_proxy'] ?? $_SERVER['NO_PROXY'] ?? '',
            \CURLOPT_SSL_VERIFYPEER => $options['verify_peer'] ?? false,
            \CURLOPT_SSL_VERIFYHOST => $options['verify_host'] ? 2 : 0,
        ];
        if(!empty($options['cafile'])){
            $curlopts[\CURLOPT_CAINFO] = $options['cafile'];
        }
        if(!empty($options['capath'])){
            $curlopts[\CURLOPT_CAPATH] = $options['capath'];
        }
        if(!empty($options['ciphers'])){
            $curlopts[\CURLOPT_SSL_CIPHER_LIST] = $options['ciphers'];
        }
        if(!empty($options['local_cert'])){
            $curlopts[\CURLOPT_SSLCERT] = $options['local_cert'];
        }
        if(!empty($options['local_pk'])){
            $curlopts[\CURLOPT_SSLKEY] = $options['local_pk'];
        }
        if(!empty($options['passphrase'])){
            $curlopts[\CURLOPT_KEYPASSWD] = $options['passphrase'];
        }
        if(!empty($options['capture_peer_cert_chain'])){
            $curlopts[\CURLOPT_CERTINFO] = $options['capture_peer_cert_chain'];
        }
        // if (1.0 === (float) $options['http_version']) {
        //     $curlopts[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_0;
        // } elseif (1.1 === (float) $options['http_version']) {
        //     $curlopts[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_1;
        // } elseif (\defined('CURL_VERSION_HTTP2') && (\CURL_VERSION_HTTP2 & CurlClientState::$curlVersion['features']) && ('https:' === $scheme || 2.0 === (float) $options['http_version'])) {
        //     $curlopts[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_2_0;
        // }

        if (isset($options['auth_ntlm'])) {
            $curlopts[\CURLOPT_HTTPAUTH] = \CURLAUTH_NTLM;
            $curlopts[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_1_1;

            if (\is_array($options['auth_ntlm'])) {
                $count = \count($options['auth_ntlm']);
                if ($count <= 0 || $count > 2) {
                    throw new InvalidArgumentException(sprintf('Option "auth_ntlm" must contain 1 or 2 elements, %d given.', $count));
                }
                $options['auth_ntlm'] = implode(':', $options['auth_ntlm']);
            }

            if (!\is_string($options['auth_ntlm'])) {
                throw new InvalidArgumentException(sprintf('Option "auth_ntlm" must be a string or an array, "%s" given.', gettype($options['auth_ntlm'])));
            }

            $curlopts[\CURLOPT_USERPWD] = $options['auth_ntlm'];
        }

        if (!\ZEND_THREAD_SAFE) {
            $curlopts[\CURLOPT_DNS_USE_GLOBAL_CACHE] = false;
        }

        if (\defined('CURLOPT_HEADEROPT') && \defined('CURLHEADER_SEPARATE')) {
            $curlopts[\CURLOPT_HEADEROPT] = \CURLHEADER_SEPARATE;
        }

        if ('POST' === $method) {
            // Use CURLOPT_POST to have browser-like POST-to-GET redirects for 301, 302 and 303
            $curlopts[\CURLOPT_POST] = true;
        } elseif ('HEAD' === $method) {
            $curlopts[\CURLOPT_NOBODY] = true;
        } else {
            $curlopts[\CURLOPT_CUSTOMREQUEST] = $method;
        }

        if ('\\' !== \DIRECTORY_SEPARATOR && $options['timeout'] < 1) {
            $curlopts[\CURLOPT_NOSIGNAL] = true;
        }

        
        if (\extension_loaded('zlib') && !isset($options['normalized_headers']['accept-encoding'])) {
            //$options['headers'][] = 'Accept-Encoding: gzip'; // Expose only one encoding, some servers mess up when more are provided
            $curlopts[\CURLOPT_ENCODING] = 'gzip';
        }

        foreach ($options['headers'] as $header) {
            if (':' === $header[-2] && \strlen($header) - 2 === strpos($header, ': ')) {
                // curl requires a special syntax to send empty headers
                $curlopts[\CURLOPT_HTTPHEADER][] = substr_replace($header, ';', -2);
            } else {
                $curlopts[\CURLOPT_HTTPHEADER][] = $header;
            }
        }

        // Prevent curl from sending its default Accept and Expect headers
        foreach (['accept', 'expect'] as $header) {
            if (!isset($options['normalized_headers'][$header][0])) {
                $curlopts[\CURLOPT_HTTPHEADER][] = $header.':';
            }
        }

        if (!\is_string($body = $options['body'])) {
            if (\is_resource($body)) {
                $curlopts[\CURLOPT_INFILE] = $body;
            } else {
                $eof = false;
                $buffer = '';
                $curlopts[\CURLOPT_READFUNCTION] = static function ($ch, $fd, $length) use ($body, &$buffer, &$eof) {
                    return self::readRequestBody($length, $body, $buffer, $eof);
                };
            }

            if (isset($options['normalized_headers']['content-length'][0])) {
                $curlopts[\CURLOPT_INFILESIZE] = substr($options['normalized_headers']['content-length'][0], \strlen('Content-Length: '));
            } elseif (!isset($options['normalized_headers']['transfer-encoding'])) {
                $curlopts[\CURLOPT_HTTPHEADER][] = 'Transfer-Encoding: chunked'; // Enable chunked request bodies
            }

            if ('POST' !== $method) {
                $curlopts[\CURLOPT_UPLOAD] = true;

                if (!isset($options['normalized_headers']['content-type'])) {
                    $curlopts[\CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
                }
            }
        } elseif ('' !== $body || 'POST' === $method) {
            $curlopts[\CURLOPT_POSTFIELDS] = $body;
        }
        if ($options['peer_fingerprint']) {
            if (!isset($options['peer_fingerprint']['pin-sha256'])) {
                throw new TransportException(__CLASS__.' supports only "pin-sha256" fingerprints.');
            }

            $curlopts[\CURLOPT_PINNEDPUBLICKEY] = 'sha256//'.implode(';sha256//', $options['peer_fingerprint']['pin-sha256']);
        }

        if ($options['bindto']) {
            if (file_exists($options['bindto'])) {
                $curlopts[\CURLOPT_UNIX_SOCKET_PATH] = $options['bindto'];
            } elseif (Str::startsWith($options['bindto'], 'if!') && preg_match('/^(.*):(\d+)$/', $options['bindto'], $matches)) {
                $curlopts[\CURLOPT_INTERFACE] = $matches[1];
                $curlopts[\CURLOPT_LOCALPORT] = $matches[2];
            } else {
                $curlopts[\CURLOPT_INTERFACE] = $options['bindto'];
            }
        }

        if (0 < $options['max_duration']) {
            $curlopts[\CURLOPT_TIMEOUT_MS] = 1000 * $options['max_duration'];
        }

        if (!empty($options['extra']['curl']) && \is_array($options['extra']['curl'])) {
            $this->validateExtraCurlOptions($options['extra']['curl']);
            $curlopts += $options['extra']['curl'];
        }

        $ch = curl_init();
        //$this->logger->info(sprintf('Request: "%s %s"', $method, $url));

        foreach ($curlopts as $opt => $value) {
            if (null !== $value && !curl_setopt($ch, $opt, $value) && \CURLOPT_CERTINFO !== $opt && (!\defined('CURLOPT_HEADEROPT') || \CURLOPT_HEADEROPT !== $opt)) {
                $constantName = $this->findConstantName($opt);
                throw new TransportException(sprintf('Curl option "%s" is not supported.', $constantName ?? $opt));
            }
        }
        return new CurlResponse($ch, $options,  $method, $url);
    }



    /**
     * @param array $options
     * @return static
     */
    public function withOptions(array $options)
    {
        $clone = clone $this;
        $clone->defaultOptions = array_merge($this->defaultOptions, $options);
        return $clone;
    }



    /**
     * @param ResponseInterface|iterable $responses
     * @param float|null $timeout
     * @return ResponseStreamInterface
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        throw new \LogicException('Not implemented.');
    }

    /**
     * Wraps the request's body callback to allow it to return strings longer than curl requested.
     */
    private static function readRequestBody(int $length, \Closure $body, string &$buffer, bool &$eof): string
    {
        if (!$eof && \strlen($buffer) < $length) {
            if (!\is_string($data = $body($length))) {
                throw new TransportException(sprintf('The return value of the "body" option callback must be a string, "%s" returned.', get_debug_type($data)));
            }

            $buffer .= $data;
            $eof = '' === $data;
        }

        $data = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length);

        return $data;
    }


    private function findConstantName(int $opt): string
    {
        $constants = array_filter(get_defined_constants(), static function ($v, $k) use ($opt) {
            return $v === $opt && 'C' === $k[0] && (Str::startsWith($k, 'CURLOPT_') || Str::startsWith($k, 'CURLINFO_'));
        }, \ARRAY_FILTER_USE_BOTH);

        return key($constants);
    }

    /**
     * Prevents overriding options that are set internally throughout the request.
     */
    private function validateExtraCurlOptions(array $options): void
    {
        $curloptsToConfig = [
            // options used in CurlHttpClient
            \CURLOPT_HTTPAUTH => 'auth_ntlm',
            \CURLOPT_USERPWD => 'auth_ntlm',
            \CURLOPT_RESOLVE => 'resolve',
            \CURLOPT_NOSIGNAL => 'timeout',
            \CURLOPT_HTTPHEADER => 'headers',
            \CURLOPT_INFILE => 'body',
            \CURLOPT_READFUNCTION => 'body',
            \CURLOPT_INFILESIZE => 'body',
            \CURLOPT_POSTFIELDS => 'body',
            \CURLOPT_UPLOAD => 'body',
            \CURLOPT_INTERFACE => 'bindto',
            \CURLOPT_TIMEOUT_MS => 'max_duration',
            \CURLOPT_TIMEOUT => 'max_duration',
            \CURLOPT_MAXREDIRS => 'max_redirects',
            \CURLOPT_PROXY => 'proxy',
            \CURLOPT_NOPROXY => 'no_proxy',
            \CURLOPT_SSL_VERIFYPEER => 'verify_peer',
            \CURLOPT_SSL_VERIFYHOST => 'verify_host',
            \CURLOPT_CAINFO => 'cafile',
            \CURLOPT_CAPATH => 'capath',
            \CURLOPT_SSL_CIPHER_LIST => 'ciphers',
            \CURLOPT_SSLCERT => 'local_cert',
            \CURLOPT_SSLKEY => 'local_pk',
            \CURLOPT_KEYPASSWD => 'passphrase',
            \CURLOPT_CERTINFO => 'capture_peer_cert_chain',
            \CURLOPT_USERAGENT => 'normalized_headers',
            \CURLOPT_REFERER => 'headers',
            // options used in CurlResponse
            \CURLOPT_NOPROGRESS => 'on_progress',
            \CURLOPT_PROGRESSFUNCTION => 'on_progress',
        ];

        if (\defined('CURLOPT_UNIX_SOCKET_PATH')) {
            $curloptsToConfig[\CURLOPT_UNIX_SOCKET_PATH] = 'bindto';
        }

        if (\defined('CURLOPT_PINNEDPUBLICKEY')) {
            $curloptsToConfig[\CURLOPT_PINNEDPUBLICKEY] = 'peer_fingerprint';
        }

        $curloptsToCheck = [
            \CURLOPT_PRIVATE,
            \CURLOPT_HEADERFUNCTION,
            \CURLOPT_WRITEFUNCTION,
            \CURLOPT_VERBOSE,
            \CURLOPT_STDERR,
            \CURLOPT_RETURNTRANSFER,
            \CURLOPT_URL,
            \CURLOPT_FOLLOWLOCATION,
            \CURLOPT_HEADER,
            \CURLOPT_CONNECTTIMEOUT,
            \CURLOPT_CONNECTTIMEOUT_MS,
            \CURLOPT_HTTP_VERSION,
            \CURLOPT_PORT,
            \CURLOPT_DNS_USE_GLOBAL_CACHE,
            \CURLOPT_PROTOCOLS,
            \CURLOPT_REDIR_PROTOCOLS,
            \CURLOPT_COOKIEFILE,
            \CURLINFO_REDIRECT_COUNT,
        ];

        if (\defined('CURLOPT_HTTP09_ALLOWED')) {
            $curloptsToCheck[] = \CURLOPT_HTTP09_ALLOWED;
        }

        if (\defined('CURLOPT_HEADEROPT')) {
            $curloptsToCheck[] = \CURLOPT_HEADEROPT;
        }

        $methodOpts = [
            \CURLOPT_POST,
            \CURLOPT_PUT,
            \CURLOPT_CUSTOMREQUEST,
            \CURLOPT_HTTPGET,
            \CURLOPT_NOBODY,
        ];

        foreach ($options as $opt => $optValue) {
            if (isset($curloptsToConfig[$opt])) {
                $constName = $this->findConstantName($opt) ?? $opt;
                throw new InvalidArgumentException(sprintf('Cannot set "%s" with "extra.curl", use option "%s" instead.', $constName, $curloptsToConfig[$opt]));
            }

            if (\in_array($opt, $methodOpts)) {
                throw new InvalidArgumentException('The HTTP method cannot be overridden using "extra.curl".');
            }

            if (\in_array($opt, $curloptsToCheck)) {
                $constName = $this->findConstantName($opt) ?? $opt;
                throw new InvalidArgumentException(sprintf('Cannot set "%s" with "extra.curl".', $constName));
            }
        }
    }
}